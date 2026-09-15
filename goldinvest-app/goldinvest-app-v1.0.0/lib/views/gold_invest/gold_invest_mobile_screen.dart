part of 'gold_invest_screen.dart';

class GoldInvestMobileScreen extends StatelessWidget {
  GoldInvestMobileScreen({super.key});
  final controller = Get.put(InvestPlanController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _bodyWidget(context),
    );
  }

  Column _bodyWidget(BuildContext context) {
    return Column(
      children: [_topDesignWidget(context)],
    );
  }

  Container _topDesignWidget(context) {
    return Container(
      color: CustomColor.blackColor.withValues(alpha: 0.05),
      child: Padding(
        padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.8),
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
            child: Obx(() => controller.isLoading
                ? const CustomLoadingAPI()
                : Column(
                    children: [CustomCardGoldPan(), inputButtonWidget(context)],
                  ))),
      ),
    );
  }

  SingleChildRenderObjectWidget inputButtonWidget(context) {
    return controller.plansList.isNotEmpty
        ? Padding(
            padding: EdgeInsets.only(
                top: Dimensions.paddingSize * 1.5,
                left: Dimensions.paddingSize,
                right: Dimensions.paddingSize),
            child: PrimaryButton(
              isLoading: controller.isLoading,
              title: Strings.investGold,
              fontWeight: FontWeight.w600,
              onPressed: () {
                controller.investAmount.text =
                    controller.minimumInvestAmount.value;
                showModalBottomSheet(
                    isScrollControlled: true,
                    context: context,
                    builder: (context) {
                      return _modelSheetWidget(context);
                    });
              },
              buttonTextColor: CustomColor.whiteColor,
              buttonColor: CustomColor.primaryLightColor,
              elevation: 0,
              borderColor: Theme.of(context).primaryColor,
              borderWidth: 1.5,
              radius: Dimensions.radius * 1.2,
              height: Dimensions.heightSize * 4.67,
            ),
          )
        : const SizedBox.shrink();
  }

  Padding _modelSheetWidget(context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: GoldInvestWidget(),
    );
  }
}
