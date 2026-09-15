import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../controller/increment_decrement/amount_controller_money_out.dart';
import '../../controller/money_out/money_out_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/buttons/primary_button.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading2_widget.dart';
import '../common/text_labels/title_heading4_widget.dart';
import '../slider_button_widget/slider_button.dart';

class MoneyOutWidget extends StatelessWidget {
  MoneyOutWidget({super.key});
  final AmountControllerMoneyOut amountController =
      Get.put(AmountControllerMoneyOut());

  final MoneyOutController controller = Get.put(MoneyOutController());

  @override
  Widget build(BuildContext context) {
    final h = MediaQuery.of(context).size.height;
    return Padding(
        padding: EdgeInsets.only(top: h * 0.15),
        child: SlideButtonWidget(
            title: Strings.moneyOut,
            action: () {
              _bottomSlideWidget(context);
            }));
  }

  Future _bottomSlideWidget(context) {
    return showModalBottomSheet(
      context: context,
      builder: (context) {
        return Container(
            padding: EdgeInsets.all(Dimensions.paddingSize),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(15),
            ),
            child: Column(
              crossAxisAlignment: crossStart,
              children: [
                Container(
                  alignment: Alignment.center,
                  child: CustomImageWidget(
                    path: Assets.icon.slideBarRectangle,
                    height: Dimensions.heightSize * 0.4,
                    width: Dimensions.widthSize * 4.2,
                  ),
                ),
                verticalSpace(Dimensions.paddingSize),
                const TitleHeading2Widget(
                  text: Strings.preview,
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                ),
                verticalSpace(Dimensions.paddingSize),
                _buildTitleHeadingRow(
                    leftHeading: Strings.withdrawAmount,
                    rightHeading:
                        "${(double.tryParse(amountController.amountController.text) ?? 0.0).toStringAsFixed(4)} ${LocalStorage.baseCurrencyCode}"),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                _buildTitleHeadingRow(
                    leftHeading: Strings.exchangeRate,
                    rightHeading:
                        " 1.0000 ${LocalStorage.baseCurrencyCode} = ${controller.exchangeRate.value.toStringAsFixed(4)} ${controller.currencyCode.value}"),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                _buildTitleHeadingRow(
                    leftHeading: Strings.fees,
                    rightHeading:
                        "${controller.getFees().toStringAsFixed(4)} ${LocalStorage.baseCurrencyCode}"),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                _buildTitleHeadingRow(
                    leftHeading: Strings.totalPayable,
                    rightHeading:
                        "${controller.getTotalPayable().toStringAsFixed(3)} ${controller.currencyCode.value}"),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                _buildTitleHeadingRow(
                    leftHeading: Strings.willGet,
                    rightHeading:
                        "${controller.willGet().toStringAsFixed(4)} ${controller.currencyCode.value}"),
                _buttonWidget(context),
              ],
            ));
      },
    );
  }

  Row _buildTitleHeadingRow({
    required String leftHeading,
    required String rightHeading,
  }) {
    return Row(
      mainAxisAlignment: mainSpaceBet,
      children: [
        TitleHeading4Widget(text: leftHeading),
        TitleHeading4Widget(
          text: rightHeading,
          fontWeight: FontWeight.w700,
        ),
      ],
    );
  }

  Padding _buttonWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.heightSize * 0.5),
      child: Column(
        children: [
          Obx(
            () => controller.isInsertLoading
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: Strings.confirm,
                    fontWeight: FontWeight.w600,
                    onPressed: () {
                      controller.manualPaymentGetGatewaysProcess();
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
        ],
      ),
    );
  }
}
