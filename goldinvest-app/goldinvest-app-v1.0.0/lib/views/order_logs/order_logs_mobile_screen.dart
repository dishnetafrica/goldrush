part of 'order_logs_screen.dart';

class OrderLogsMobileScreen extends StatelessWidget {
  OrderLogsMobileScreen({super.key});
  final dateOnly = DateFormat('dd');
  final monthOnly = DateFormat('MMM');

  final controller = Get.put(LogsController());

  @override
  Widget build(BuildContext context) {
    return PopScope(
      onPopInvokedWithResult: (didPop, result) {
        Get.toNamed(Routes.home);
      },
      child: WillPopScope(
        onWillPop: () async {
          Get.toNamed(Routes.home);
          return true;
        },
        child: Scaffold(
          appBar: PrimaryAppBar(
            Strings.orderLog,
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
                child: Obx(() => controller.isLoadingOrder
                    ? const CustomLoadingAPI()
                    : _bodyWidget(context)),
              )
            ],
          ),
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
        child: controller.orders.isEmpty
            ? Center(
                child: Text(
                  DynamicLanguage.key(Strings.noOrdersAvailable),
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: Dimensions.headingTextSize4,
                  ),
                ),
              )
            : ListView.builder(
                itemCount: controller.orders.length,
                padding: EdgeInsets.zero,
                itemBuilder: (context, index) {
                  var data = controller.orders[index];
                  return Padding(
                    padding: EdgeInsets.only(
                        left: Dimensions.paddingSize,
                        right: Dimensions.paddingSize),
                    child: CustomWalletCard(
                      statusWidget: Container(
                        color: CustomColor.whiteColor,
                      ),
                      isSelected: true,
                      fontWeightTitle: FontWeight.w700,
                      walletTitle: data.item,
                      amount:
                          "${data.totalAmount.toStringAsFixed(0)} ${LocalStorage.baseCurrencyCode}",
                      fontSizeTitle: Dimensions.headingTextSize4,
                      fontWeightSubTitleMain: FontWeight.w400,
                      fontSizeSubtitle: Dimensions.headingTextSize4,
                      customWidget: DateInfoWidget(
                        dateText: dateOnly.format(data.date),
                        monthText: monthOnly.format(data.date),
                      ),
                    ),
                  );
                },
              ),
      ),
    );
  }
}
