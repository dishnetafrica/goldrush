part of 'send_money_screen.dart';

class SendMoneyMobileScreen extends StatelessWidget {
  SendMoneyMobileScreen({super.key});
  final SendMoneyController controller = Get.put(SendMoneyController());
  final controllerAmount = Get.put(AmountControllerSendMoney());

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        Get.toNamed(Routes.home);
        return true;
      },
      child: Scaffold(
        appBar: PrimaryAppBar(
          backgroundColor: CustomColor.blackColor.withValues(alpha: 0.01),
          Strings.sendMoney,
          showBackButton: true,
          leading: BackButtonWidget(
            onTap: () {
              Get.toNamed(Routes.home);
            },
          ),
        ),
        body: Obx(
          () => controller.isLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),
        ),
        // _bodyWidget(context),
      ),
    );
  }

  Padding _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.all(Dimensions.paddingSize),
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: crossCenter,
          children: [
            _headerDesign(context),
            _amountDesign(context),
            _inputWidget(context),
            _calculatedWidget(context),
            _sliderToActWidget(context),
          ],
        ),
      ),
    );
  }

  Column _headerDesign(context) {
    return Column(
      //crossAxisAlignment: crossCenter,
      children: [
        TitleHeading1Widget(
          text:
              "${controller.availableBalance.value.toStringAsFixed(2)} ${LocalStorage.baseCurrencyCode}",
          fontSize: Dimensions.headingTextSize4 * 2,
        ),
        const TitleHeading3Widget(
          text: Strings.currentBalance,
          color: CustomColor.liteBlack2,
        ),
        verticalSpace(Dimensions.heightSize * 2),
      ],
    );
  }

  AmountDesign _amountDesign(context) {
    return AmountDesign(
      counterController: controllerAmount,
    );
  }

  PrimaryInputWidget _inputWidget(context) {
    return PrimaryInputWidget(
      validator: true,
      hintText: Strings.receiverEmailAddress,
      prefixIconPath: Assets.icon.sms,
      textInputType: TextInputType.text,
      textController: controller.receiverEmail,
    );
  }

  CalculatedWidget _calculatedWidget(context) {
    return CalculatedWidget();
  }

  SendMoneyBottomSheet _sliderToActWidget(BuildContext context) {
    return SendMoneyBottomSheet();
  }
}
