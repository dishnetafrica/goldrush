part of 'money_out_screen.dart';

class MoneyOutMobileScreen extends StatelessWidget {
  MoneyOutMobileScreen({super.key});
  final controller = Get.put(MoneyOutController());
  final controllerAmount = Get.put(AmountControllerMoneyOut());

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
          Strings.moneyOut,
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
            _selectBalanceWidget(context),
            _amountDesign(context),
            _paymentGatewayWidget(context),
            _calculatedWidget(context),
            _sliderToActWidget(context)
          ],
        ),
      ),
    );
  }

  Column _headerDesign(context) {
    return Column(
      children: [
        TitleHeading1Widget(
          text:
              "${controller.availableBalance.value.toStringAsFixed(2)} ${LocalStorage.baseCurrencyCode}",
          fontSize: Dimensions.headingTextSize4 * 2,
          fontWeight: FontWeight.w800,
        ),
        const TitleHeading3Widget(
          text: Strings.currentBalance,
          color: CustomColor.liteBlack2,
        )
      ],
    );
  }

  AmountDesign _amountDesign(context) {
    return AmountDesign(
      counterController: controllerAmount,
    );
  }

  Padding _selectBalanceWidget(context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize),
      child: CustomDropdownMenu<balanceTypes.Type>(
        hintText: Strings.selectBalanceType,
        iconPath: Assets.icon.money2,
        selectMethod: controller.selectBalanceType,
        itemsList: controller.sendingBalanceTypeList,
        onChanged: (value) {
          controller.selectBalanceType.value = value!.name;
          controller.selectBalanceValue.value = value.value;
          if (controller.selectBalanceType.value == "Profit Balance") {
            controller.availableBalance.value = controller
                .moneyOutWalletAndGatewaysModel
                .data
                .userWallets
                .first
                .profitBalance
                .toDouble();
          } else {
            controller.availableBalance.value = controller
                .moneyOutWalletAndGatewaysModel.data.userWallets.first.balance
                .toDouble();
          }
        },
      ),
    );
  }

  CustomDropdownMenu<balanceTypes.PaymentGateway> _paymentGatewayWidget(context) {
    return CustomDropdownMenu<PaymentGateway>(
      hintText: Strings.selectPaymentGateway,
      iconPath: Assets.icon.cardPos,
      selectMethod: controller.selectPaymentGateway,
      itemsList: controller.sendingGatewayList,
      onChanged: (value) {
        controller.selectPaymentGateway.value = value!.name;

        controller.selectCurrency.value = value.currencies.first.alias;
        controller.currencyCode.value = value.currencies.first.currencyCode;

        controller.exchangeRate.value = value.currencies.first.rate;

        controller.minLimit.value = value.currencies.first.minLimit.toDouble() /
            value.currencies.first.rate;
        controller.maxLimit.value = value.currencies.first.maxLimit.toDouble() /
            value.currencies.first.rate;

        controller.fixedCharge.value =
            value.currencies.first.fixedCharge.toDouble();
        controller.percentCharge.value =
            value.currencies.first.percentCharge.toDouble();
      },
    );
  }

  Visibility _calculatedWidget(context) {
    return Visibility(
      visible: controller.selectPaymentGateway.value != "",
      child: Column(
        mainAxisAlignment: mainSpaceBet,
        children: [
          verticalSpace(Dimensions.paddingSize),
          CustomTextSpanWidget(
            firstText: Strings.exchangeRate,
            firstTextColor: CustomColor.blackColor.withValues(alpha: 0.4),
            secondText:
                " 1${LocalStorage.baseCurrencyCode} = ${controller.exchangeRate}  ${controller.currencyCode.value}",
          ),
          verticalSpace(Dimensions.paddingSize * 0.5),
          CustomTextSpanWidget(
            firstText: Strings.limit,
            firstTextColor: CustomColor.blackColor.withValues(alpha: 0.4),
            secondText:
                " ${controller.minLimit.toStringAsFixed(4)} ${LocalStorage.baseCurrencyCode} = ${controller.maxLimit.toStringAsFixed(4)} ${LocalStorage.baseCurrencyCode}",
          ),
          verticalSpace(Dimensions.paddingSize * 0.5),
          CustomTextSpanWidget(
            firstText: Strings.charge,
            firstTextColor: CustomColor.blackColor.withValues(alpha: 0.4),
            secondText:
                " ${controller.fixedCharge} ${LocalStorage.baseCurrencyCode} + ${controller.percentCharge}%",
          ),
        ],
      ),
    );
  }

  MoneyOutWidget _sliderToActWidget(BuildContext context) {
    return MoneyOutWidget();
  }
}
