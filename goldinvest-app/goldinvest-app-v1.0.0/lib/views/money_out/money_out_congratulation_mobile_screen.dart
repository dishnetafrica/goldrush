part of 'money_out_congratulation_screen.dart';

class MoneyOutCongratulationMobileScreen extends StatelessWidget {
  MoneyOutCongratulationMobileScreen({super.key});

  final MoneyOutController controller = Get.put(MoneyOutController());
  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        Get.toNamed(Routes.home);
        return true;
      },
      child: Scaffold(
        body: _bodyWidget(context),
      ),
    );
  }

  Column _bodyWidget(BuildContext context) {
    return Column(
      children: [_topDesign(context)],
    );
  }

  Stack _topDesign(context) {
    return Stack(
      children: [
        Column(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Stack(
              children: [
                Positioned(
                  child: Container(
                    height: Dimensions.heightSize * 20,
                    color: CustomColor.blackColor.withValues(alpha: 0.05),
                  ),
                ),
                PrimaryAppBar(
                  Strings.congratulations,
                  showBackButton: false,
                  backgroundColor: CustomColor.blackColor.withValues(alpha: 0.01),
                ),
              ],
            ),
            _moneyInfoWidget(context),
          ],
        ),
        Positioned(
            left: Dimensions.widthSize * 9,
            top: Dimensions.heightSize * 14,
            child: SizedBox(
              height: Dimensions.heightSize * 12.72,
              width: Dimensions.widthSize * 20.8,
              child: CustomImageWidget(path: Assets.icon.tickIcon2),
            ))
      ],
    );
  }

  Padding _moneyInfoWidget(context) {
    return Padding(
      padding: EdgeInsets.all(Dimensions.paddingSize),
      child: Column(
        mainAxisAlignment: mainCenter,
        children: [
          verticalSpace(Dimensions.heightSize * 8),
          TitleHeading3Widget(
            text: Strings.moneyOut,
            color: CustomColor.blackColor.withValues(alpha: 0.4),
          ),
          TitleHeading3Widget(
            text:
                "${controller.amountController.amountController.text} ${LocalStorage.baseCurrencyCode}",
            fontSize: Dimensions.headingTextSize3 * 2,
          ),
          ItemBuilderWidget(
            left: Strings.fees,
            right: "${controller.getFees()} ${LocalStorage.baseCurrencyCode}",
          ),
          Divider(
            height: Dimensions.heightSize * 2.5,
            color: CustomColor.blackColor.withValues(alpha: 0.1),
            thickness: 1,
          ),
          ItemBuilderWidget(
            left: Strings.totalPayable,
            right:
                "${controller.totalPayable.value} ${LocalStorage.baseCurrencyCode}",
          ),
          Divider(
            height: Dimensions.heightSize * 2.5,
            color: CustomColor.blackColor.withValues(alpha: 0.1),
            thickness: 1,
          ),
          ItemBuilderWidget(
            left: Strings.willGet,
            right:
                "${controller.amountController.amountController.text} ${LocalStorage.baseCurrencyCode}",
          ),
          Divider(
            height: Dimensions.heightSize * 2.5,
            color: CustomColor.blackColor.withValues(alpha: 0.1),
            thickness: 1,
          ),
          verticalSpace(Dimensions.heightSize * 0.1),
          TextButton(
            onPressed: () => Get.toNamed(Routes.transactionLogs),
            child: const TitleHeading3Widget(
              text: Strings.transactionsHistory,
              color: CustomColor.primaryLightColor,
              fontWeight: FontWeight.w500,
            ),
          ),
          _buttonWidget(context),
        ],
      ),
    );
  }

  Padding _buttonWidget(BuildContext context) {
    final h = MediaQuery.of(context).size.height;
    return Padding(
      padding: EdgeInsets.only(
        top: h * 0.001,
      ),
      child: Column(
        children: [
          PrimaryButton(
            title: Strings.backToDashboard,
            fontWeight: FontWeight.w600,
            onPressed: () {
              Get.toNamed(Routes.home);

              controller.amountController.amountController.clear();
            },
            buttonTextColor: CustomColor.whiteColor,
            buttonColor: CustomColor.primaryLightColor,
            elevation: 0,
            borderColor: Theme.of(context).primaryColor,
            borderWidth: 1.5,
            radius: Dimensions.radius * 1.2,
            height: Dimensions.heightSize * 4.67,
          ),
        ],
      ),
    );
  }
}
