part of 'transaction_screen.dart';

class TransactionLogsMobileScreen extends StatelessWidget {
  TransactionLogsMobileScreen({super.key});
  final controller = Get.put(LogsController());
  final dateOnly = DateFormat('dd');
  final monthOnly = DateFormat('MMM');

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        Get.toNamed(Routes.home);
        return true;
      },
      child: Scaffold(
        appBar: PrimaryAppBar(
          Strings.transactions,
          showBackButton: true,
          leading: BackButtonWidget(
            onTap: () {
              Get.toNamed(Routes.home);
            },
          ),
        ),
        body: Column(
          children: [
            Expanded(
              child: Obx(() => controller.isLoadingTransaction
                  ? const CustomLoadingAPI()
                  : _bodyWidget(context)),
            )
          ],
        ),
      ),
    );
  }

  Container _bodyWidget(context) {
    return Container(
      color: CustomColor.blackColor.withValues(alpha: 0.05),
      child: Container(
        decoration: BoxDecoration(
          boxShadow: [
            BoxShadow(
              color: CustomColor.blackColor.withValues(alpha: 0.07),
              spreadRadius: 0.5,
            ),
          ],
          color: CustomColor.whiteColor,
          borderRadius: BorderRadius.only(
            topRight: Radius.circular(Dimensions.radius * 2),
            topLeft: Radius.circular(Dimensions.radius * 2),
          ),
        ),
        child: controller.transactions.isEmpty
            ? Center(
                child: Text(
                  DynamicLanguage.key(Strings.noTransactionsAvailable),
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: Dimensions.headingTextSize4,
                  ),
                ),
              )
            : ListView.builder(
                itemCount: controller.transactions.length,
                padding: EdgeInsets.zero,
                itemBuilder: (context, index) {
                  var data = controller.transactions[index];
                  return Padding(
                    padding: EdgeInsets.only(
                        left: Dimensions.paddingSize,
                        right: Dimensions.paddingSize),
                    child: CustomWalletCard(
                      statusColor: _getStatusColor(data.status),
                      statusText: _getStatusText(data.status),
                      status: data.status,
                      isSelected: true,
                      fontWeightTitle: FontWeight.w700,
                      walletTitle: data.type,
                      fontSizeTitle: Dimensions.headingTextSize4,
                      walletSubTitle: data.trxId,
                      fontWeightSubTitleMain: FontWeight.w400,
                      fontSizeSubtitle: Dimensions.headingTextSize4,
                      amount:
                          "${data.receiveAmount.toStringAsFixed(2)} ${data.requestCurrency}",
                      customWidget: DateInfoWidget(
                        dateText: dateOnly.format(data.createdAt),
                        monthText: monthOnly.format(data.createdAt),
                      ),
                    ),
                  );
                },
              ),
      ),
    );
  }

  Color _getStatusColor(int status) {
    switch (status) {
      case 1:
        return CustomColor.greenColor.withValues(alpha: 0.9);
      case 2:
        return CustomColor.orangeColor.withValues(alpha: 0.9);
      case 3:
        return CustomColor.pinkColor.withValues(alpha: 0.9);
      case 4:
        return CustomColor.redColor.withValues(alpha: 0.9);
      default:
        return CustomColor.orangeColor;
    }
  }

  String _getStatusText(int status) {
    switch (status) {
      case 1:
        return Strings.success;
      case 2:
        return Strings.pending;
      case 3:
        return Strings.hold;
      case 4:
        return Strings.rejected;
      default:
        return Strings.waiting;
    }
  }
}
