part of 'send_money_congratulation_screen.dart';

// ignore: must_be_immutable
class SendMoneyCongratulationMobileScreen extends StatelessWidget {
  SendMoneyCongratulationMobileScreen({super.key});
  SendMoneyController controller = Get.put(SendMoneyController());
  final NavigationController navController = Get.put(NavigationController());

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
    final h = MediaQuery.of(context).size.height;

    return Stack(
      children: [
        Column(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Stack(
              children: [
                Positioned(
                  child: Container(
                    height: h * 0.3,
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
            _addMoneyInfoWidget(context),
          ],
        ),
        Positioned(
            left: Dimensions.widthSize * 9,
            top: Dimensions.heightSize * 10,
            child: SizedBox(
              height: Dimensions.heightSize * 12.72,
              width: Dimensions.widthSize * 20.8,
              child: CustomImageWidget(path: Assets.icon.tickIcon2),
            ))
      ],
    );
  }

  Padding _addMoneyInfoWidget(context) {
    return Padding(
      padding: EdgeInsets.all(Dimensions.paddingSize),
      child: Column(
        mainAxisAlignment: mainCenter,
        children: [
          verticalSpace(Dimensions.heightSize * 8),
          TitleHeading3Widget(
            text: Strings.sendMoney,
            color: CustomColor.blackColor.withValues(alpha: 0.4),
          ),
          TitleHeading3Widget(
            text:
                "${controller.sendMoneyAmount.value} ${LocalStorage.baseCurrencyCode}",
            fontSize: Dimensions.headingTextSize3 * 2,
          ),
          _itemBuilderWidget(context, Strings.fees,
              "${controller.getFees()} ${LocalStorage.baseCurrencyCode}"),
          Divider(
            height: Dimensions.heightSize * 2.5,
            color: CustomColor.blackColor.withValues(alpha: 0.1),
            thickness: 1,
          ),
          _itemBuilderWidget(context, Strings.totalPayable,
              "${controller.getTotalPayable()} ${LocalStorage.baseCurrencyCode}"),
          Divider(
            height: Dimensions.heightSize * 2.5,
            color: CustomColor.blackColor.withValues(alpha: 0.1),
            thickness: 1,
          ),
          _itemBuilderWidget(context, Strings.willGet,
              "${controller.sendMoneyAmount.value} ${LocalStorage.baseCurrencyCode}"),
          Divider(
            height: Dimensions.heightSize * 2.5,
            color: CustomColor.blackColor.withValues(alpha: 0.1),
            thickness: 1,
          ),
          verticalSpace(Dimensions.heightSize * 0.1),
          TextButton(
            onPressed: () {
              Get.offAllNamed(Routes.transactionLogs);
              controller.amountController.amountController.clear();
            },
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

  Row _itemBuilderWidget(BuildContext context, String left, String right) {
    return Row(
      mainAxisAlignment: mainSpaceBet,
      children: [
        TitleHeading4Widget(
          text: left,
          fontWeight: FontWeight.w400,
        ),
        TitleHeading4Widget(
          text: right,
          fontWeight: FontWeight.w700,
        ),
      ],
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
