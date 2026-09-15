part of 'gold_invest_congratulation_screen.dart';

class GoldInvestCongratulationMobileScreen extends StatelessWidget {
  GoldInvestCongratulationMobileScreen({super.key});
  final controller = Get.put(InvestPlanController());

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
        PrimaryAppBar(
          backgroundColor: CustomColor.blackColor.withValues(alpha: 0.01),
          Strings.congratulations,
          showBackButton: false,
        ),
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
              ],
            ),
            _goldInvestInfoWidget(context),
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

  Padding _goldInvestInfoWidget(context) {
    return Padding(
      padding: EdgeInsets.all(Dimensions.paddingSize),
      child: Column(
        mainAxisAlignment: mainCenter,
        children: [
          verticalSpace(Dimensions.heightSize * 8),
          TitleHeading3Widget(
            text: controller.selectPlan.value,
            color: CustomColor.blackColor.withValues(alpha: 0.4),
          ),
          TitleHeading3Widget(
            text:
                '${controller.investAmount.text} ${LocalStorage.baseCurrencyCode}',
            fontSize: Dimensions.headingTextSize3 * 2,
          ),
          _itemBuilderWidget(
              context, Strings.duration, '${controller.duration.value} Days'),
          Divider(
            height: Dimensions.heightSize * 2,
            color: CustomColor.blackColor.withValues(alpha: 0.05),
            thickness: 1,
          ),
          _itemBuilderWidget(context, Strings.fixedProfit,
              '${controller.fixedProfit.value} ${LocalStorage.baseCurrencyCode}'),
          Divider(
            height: Dimensions.heightSize * 2,
            color: CustomColor.blackColor.withValues(alpha: 0.05),
            thickness: 1,
          ),
          _itemBuilderWidget(context, Strings.percentProfit,
              '${controller.percentProfit.value} %'),
          Divider(
            height: Dimensions.heightSize * 2,
            color: CustomColor.blackColor.withValues(alpha: 0.05),
            thickness: 1,
          ),
          _itemBuilderWidget(context, Strings.profitAmount,
              '${controller.getProfitAmount()} ${LocalStorage.baseCurrencyCode}'),
          verticalSpace(Dimensions.heightSize * 0.5),
          TextButton(
            onPressed: () {
              Get.offAll(InvestmentMobileScreen(
                selectedPage: 2,
              ));
              controller.investAmount.clear();
            },
            child: const TitleHeading3Widget(
              text: Strings.investmentHistory,
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
          color: CustomColor.liteBlack2,
        ),
        TitleHeading4Widget(
          text: right,
          fontWeight: FontWeight.w700,
          color: CustomColor.primaryLightTextColor,
        ),
      ],
    );
  }

  Padding _buttonWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        top: Dimensions.heightSize * 0.01,
      ),
      child: Column(
        children: [
          PrimaryButton(
            title: Strings.backToDashboard,
            fontWeight: FontWeight.w600,
            onPressed: () {
              Get.toNamed(Routes.home);
              controller.investAmount.clear();
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
