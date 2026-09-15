<?php

namespace App\Notifications\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendMoneyMail extends Notification
{
    use Queueable;
    protected $form_data;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($form_data)
    {
        $this->form_data = $form_data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $form_data = $this->form_data;
        return (new MailMessage)
                ->greeting("Hello ".$form_data['sfullname']." !")
                ->subject("Send Money From ".$form_data['sender_amount']. ' ' .$form_data['sender_currency']." to ".$form_data['receiver_amount'].' '. $form_data['receiver_currency']." Successfully")
                ->line("You have sent money successfully to ".$form_data['rfullname'])
                ->line("Transaction ID : " .$form_data['trx_id'])
                ->line("Sender Amount : " . get_amount($form_data['sender_amount'],$form_data['sender_currency'],2))
                ->line("Exchange Rate : " ." 1 ". $form_data['sender_currency'].' = '. get_amount($form_data['exchange_rate'],4).' '.$form_data['receiver_currency'])
                ->line("Fees & Charges : " . get_amount($form_data['total_charge'],$form_data['sender_currency'],2))
                ->line("Receiver Amount : " .  get_amount($form_data['receiver_amount'],$form_data['receiver_currency'],2))
                ->line("Status : "."Success")
                ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
