<?php

namespace App\Console\Commands;

use App\GoldTrading\Models\CapitalAllocation;
use App\GoldTrading\Models\GoldLot;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Records the terms agreed for one deal. Terms are negotiated per lot, so they
 * are stored against the lot, and against a single investor when that investor's
 * terms differ from the rest of the deal.
 */
class GoldSetTermsCommand extends Command
{
    protected $signature = 'gold:set-terms
                            {lot : lot code}
                            {--investor-share= : percentage of the deal profit owed to investors}
                            {--expense-policy= : deal_before_split or company_share}
                            {--note= : why these terms were agreed}
                            {--investor= : apply the share to one investor only (username or email)}';

    protected $description = 'Record the profit share and expense policy agreed for a gold deal';

    public function handle(): int
    {
        $lot = GoldLot::where('lot_code', $this->argument('lot'))->first();

        if (! $lot) {
            $this->error('No lot found with code ' . $this->argument('lot'));

            return self::FAILURE;
        }

        $share = $this->option('investor-share');
        $policy = $this->option('expense-policy');

        if ($policy !== null && ! in_array($policy, [GoldLot::EXPENSES_DEAL_BEFORE_SPLIT, GoldLot::EXPENSES_COMPANY_SHARE], true)) {
            $this->error('--expense-policy must be deal_before_split or company_share');

            return self::FAILURE;
        }

        if ($share !== null && (! is_numeric($share) || $share < 0 || $share > 100)) {
            $this->error('--investor-share must be a number between 0 and 100');

            return self::FAILURE;
        }

        // Terms for one investor inside the deal.
        if ($investorNeedle = $this->option('investor')) {
            if ($share === null) {
                $this->error('--investor needs --investor-share');

                return self::FAILURE;
            }

            $user = User::where('username', $investorNeedle)->orWhere('email', $investorNeedle)->first();
            if (! $user) {
                $this->error('No user matches --investor=' . $investorNeedle);

                return self::FAILURE;
            }

            $allocation = CapitalAllocation::where('gold_lot_id', $lot->id)->where('user_id', $user->id)->first();
            if (! $allocation) {
                $this->error($user->username . ' has no capital allocated to ' . $lot->lot_code);

                return self::FAILURE;
            }

            $allocation->update(['share_percent' => (float) $share]);
            $this->info($user->username . ' now takes ' . $share . '% of this deal\'s profit on their capital.');

            return self::SUCCESS;
        }

        $changes = [];
        if ($share !== null) {
            $changes['investor_share_percent'] = (float) $share;
        }
        if ($policy !== null) {
            $changes['expense_policy'] = $policy;
        }
        if ($note = $this->option('note')) {
            $changes['terms_note'] = $note;
        }

        if ($changes === []) {
            $this->warn('Nothing to change. Pass --investor-share, --expense-policy or --note.');

            return self::SUCCESS;
        }

        $lot->update($changes);

        $this->info('Terms recorded for ' . $lot->lot_code . ':');
        $this->table(['Term', 'Value'], [
            ['Investor share of profit', $lot->investor_share_percent !== null ? $lot->investor_share_percent . ' %' : 'not set'],
            ['Expense policy', $lot->expense_policy],
            ['Note', $lot->terms_note ?: '-'],
        ]);

        return self::SUCCESS;
    }
}
