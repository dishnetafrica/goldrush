part of 'add_money_screen.dart';

class AddMoneyMobileScreenLayout extends StatelessWidget {
  AddMoneyMobileScreenLayout({super.key});
  final controller = Get.put(AddMoneyController());
  final controllerAmount = Get.put(AmountControllerAddMoney());

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        Get.toNamed(Routes.home);
        return true;
      },
      child: Scaffold(
        appBar: PrimaryAppBar(
          backgroundColor: CustomColor.whiteColor,
          Strings.addMoney,
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
            _sliderToActWidget(context)
          ],
        ),
      ),
    );
  }

  Padding _headerDesign(context) {
    return Padding(
      padding: EdgeInsets.only(bottom: Dimensions.paddingSize),
      child: Column(
        children: [
          TitleHeading1Widget(
            text:
                "${controller.availableBalance.value} ${LocalStorage.baseCurrencyCode}",
            fontSize: Dimensions.headingTextSize4 * 2,
            fontWeight: FontWeight.w800,
          ),
          const TitleHeading3Widget(
            text: Strings.currentBalance,
            color: CustomColor.liteBlack2,
          )
        ],
      ),
    );
  }

  AmountDesign _amountDesign(context) {
    return AmountDesign(
      counterController: controllerAmount,
    );
  }

  Padding _inputWidget(context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
      child: CustomDropdownMenu<Currency>(
        hintText: Strings.selectPaymentGateway,
        iconPath: Assets.icon.cardPos,
        selectMethod: controller.selectPaymentGateway,
        itemsList: controller.sendingCurrencyList,
        onChanged: (value) {
          controller.selectPaymentGateway.value = value!.name;
          controller.selectPaymentGatewayType.value = value.type!;
          controller.selectPaymentGatewayName.value =
              value.selectPaymentGatewayName!;

          controller.selectCurrency.value = value.alias;
          controller.currencyCode.value = value.currencyCode;

          controller.gatewayRate.value = value.rate;
          controller.minimumLimit.value = value.minLimit / value.rate;
          controller.maximumLimit.value = value.maxLimit / value.rate;

          controller.fixedCharge.value = value.fixedCharge;
          controller.percentCharge.value = value.percentCharge;
        },
      ),
    );
  }

  Visibility _calculatedWidget(context) {
    return Visibility(
      visible: controller.selectPaymentGatewayType.value != "",
      child: Column(
        mainAxisAlignment: mainSpaceBet,
        children: [
          verticalSpace(Dimensions.paddingSize),
          CustomTextSpanWidget(
            firstText: Strings.exchangeRate,
            firstTextColor: CustomColor.blackColor.withValues(alpha: 0.4),
            secondText:
                " 1 ${LocalStorage.baseCurrencyCode} = ${controller.gatewayRate.value.toStringAsFixed(4)} ${controller.currencyCode.value}",
          ),
          verticalSpace(Dimensions.paddingSize * 0.5),
          CustomTextSpanWidget(
            firstText: Strings.limit,
            firstTextColor: CustomColor.blackColor.withValues(alpha: 0.4),
            secondText:
                " ${controller.minimumLimit.toStringAsFixed(4)} ${LocalStorage.baseCurrencyCode} = ${controller.maximumLimit.toStringAsFixed(4)} ${LocalStorage.baseCurrencyCode}",
          ),
          verticalSpace(Dimensions.paddingSize * 0.5),
          CustomTextSpanWidget(
            firstText: Strings.charge,
            firstTextColor: CustomColor.blackColor.withValues(alpha: 0.4),
            secondText:
                " ${controller.fixedCharge} ${controller.currencyCode.value} + ${controller.percentCharge.toStringAsFixed(4)}%",
          ),
        ],
      ),
    );
  }

  AddMoneyBottomSheet _sliderToActWidget(BuildContext context) {
    return AddMoneyBottomSheet();
  }
}
