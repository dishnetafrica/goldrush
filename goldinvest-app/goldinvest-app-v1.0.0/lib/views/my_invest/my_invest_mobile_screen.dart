part of 'my_invest_screen.dart';

class MyInvestMobileScreen extends StatelessWidget {
  MyInvestMobileScreen({super.key});
  final MyInvestmentController controller = Get.put(MyInvestmentController());

  @override
  Widget build(BuildContext context) {
    return PopScope(
      child: Scaffold(
        body: _bodyWidget(context),
      ),
    );
  }

  Column _bodyWidget(BuildContext context) {
    return Column(
      children: [_customWalletWidget(context)],
    );
  }

  Container _customWalletWidget(BuildContext context) {
    final screenHeight = MediaQuery.of(context).size.height;
    final dateOnly = DateFormat('dd');
    final monthOnly = DateFormat('MMM');

    return Container(
      color: CustomColor.blackColor.withValues(alpha: 0.05),
      child: Padding(
        padding: EdgeInsets.only(top: Dimensions.paddingSize * 1.5),
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
            height: screenHeight * 0.7,
            child: Obx(
              () => controller.isLoading
                  ? const CustomLoadingAPI()
                  : controller.investments!.isEmpty
                      ? RefreshIndicator(
                          color: CustomColor.whiteColor,
                          backgroundColor: CustomColor.primaryLightColor,
                          strokeWidth: 2.5,
                          onRefresh: () async {
                            controller.myInvestmentsProcess();
                            return Future<void>.delayed(
                                const Duration(seconds: 3));
                          },
                          child: ListView(
                            children: [
                              SizedBox(
                                height:
                                    MediaQuery.of(context).size.height * 0.6,
                                child: Center(
                                  child: Text(
                                    DynamicLanguage.key(
                                        Strings.noInvestAvailable),
                                    style: TextStyle(
                                      fontWeight: FontWeight.w700,
                                      fontSize: Dimensions.headingTextSize4,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        )
                      : RefreshIndicator(
                          color: CustomColor.whiteColor,
                          backgroundColor: CustomColor.primaryLightColor,
                          strokeWidth: 2.5,
                          onRefresh: () async {
                            controller.myInvestmentsProcess();
                            return Future<void>.delayed(
                                const Duration(seconds: 3));
                          },
                          child: ListView.builder(
                            itemCount: controller.investments!.length,
                            itemBuilder: (context, index) {
                              var investments = controller.investments;
                              return Padding(
                                padding: EdgeInsets.only(
                                  left: Dimensions.paddingSize,
                                  right: Dimensions.paddingSize,
                                ),
                                child: GestureDetector(
                                  onTap: () {
                                    showModalBottomSheet(
                                      context: context,
                                      builder: (context) {
                                        return MyInvestWidgets(index: index);
                                      },
                                    );
                                  },
                                  child: CustomWalletCard(
                                    statusColor: _getStatusColor(
                                        investments![index].status),
                                    statusText: _getStatusText(
                                        investments[index].status),
                                    status: investments[index].status,
                                    isSelected: true,
                                    walletTitle:
                                        investments[index].investPlan.name,
                                    walletSubTitle: investments[index]
                                        .investPlan
                                        .profitReturnType,
                                    fontWeightSubTitleMain: FontWeight.w400,
                                    fontSizeSubtitle:
                                        Dimensions.headingTextSize4,
                                    fontSizeTitle: Dimensions.headingTextSize4,
                                    amount:
                                        "${investments[index].investAmount} ${LocalStorage.baseCurrencyCode}",
                                    balanceText:
                                        "${investments[index].investPlan.profit} ${LocalStorage.baseCurrencyCode}",
                                    customWidget: DateInfoWidget(
                                      dateText: dateOnly
                                          .format(investments[index].expAt),
                                      monthText: monthOnly
                                          .format(investments[index].expAt),
                                    ),
                                  ),
                                ),
                              );
                            },
                          ),
                        ),
            )),
      ),
    );
  }

  Color _getStatusColor(int status) {
    switch (status) {
      case 1:
        return CustomColor.greenColor.withValues(alpha: 0.9);
      case 2:
        return CustomColor.orangeColor.withValues(alpha: 0.9);

      default:
        return CustomColor.redColor.withValues(alpha: 0.9);
    }
  }

  String _getStatusText(int status) {
    switch (status) {
      case 1:
        return Strings.complete;
      case 2:
        return Strings.running;

      default:
        return Strings.cencel;
    }
  }
}
