// ignore_for_file: public_member_api_docs, sort_constructors_first
part of 'gold_store_congratulation_screen.dart';

class GoldStoreCongratulationMobileScreen extends StatelessWidget {
  GoldStoreCongratulationMobileScreen({
    super.key,
    required this.checkOutSuccessModel,
  });

  final CheckoutSuccessModel checkOutSuccessModel;

  final CheckoutController controller = Get.put(CheckoutController());

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

  ListView _bodyWidget(BuildContext context) {
    return ListView(
      reverse: true,
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
                Positioned(
                  child: PrimaryAppBar(
                    Strings.congratulations,
                    showBackButton: false,
                    backgroundColor: CustomColor.blackColor.withValues(alpha: 0.01),
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
            text: checkOutSuccessModel.data.item,
            color: CustomColor.blackColor.withValues(alpha: 0.4),
          ),
          TitleHeading3Widget(
            text: checkOutSuccessModel.data.price.toStringAsFixed(0),
            fontSize: Dimensions.headingTextSize3 * 2,
          ),
          verticalSpace(Dimensions.heightSize * 2),
          _itemBuilderWidget(context, Strings.paymentType,
              checkOutSuccessModel.data.paymentType),
          Divider(
            height: Dimensions.heightSize * 2.5,
            color: CustomColor.blackColor.withValues(alpha: 0.1),
            thickness: 1,
          ),
          _itemBuilderWidget(context, Strings.totalPayable,
              "${checkOutSuccessModel.data.totalAmount}"),
          verticalSpace(Dimensions.heightSize * 4),
          TextButton(
            onPressed: () => Get.offAllNamed(Routes.orderLogs),
            child: const TitleHeading3Widget(
              text: Strings.purchaseHistory,
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
    return Padding(
      padding: EdgeInsets.only(
        top: Dimensions.heightSize * 2,
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
