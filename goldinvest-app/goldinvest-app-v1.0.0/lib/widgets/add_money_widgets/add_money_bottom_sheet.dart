import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/controller/increment_decrement/amount_controller_add_money.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../controller/add_money_controller/add_money_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/buttons/primary_button.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading2_widget.dart';
import '../slider_button_widget/slider_button.dart';
import '../title_heading_row/title_heading_row.dart';

class AddMoneyBottomSheet extends StatelessWidget {
  final AmountControllerAddMoney amountController =
      Get.put(AmountControllerAddMoney());
  final controller = Get.put(AddMoneyController());

  AddMoneyBottomSheet({super.key});

  @override
  Widget build(BuildContext context) {
    final h = MediaQuery.of(context).size.height;
    return Padding(
        padding: EdgeInsets.only(top: h * 0.2),
        child: SlideButtonWidget(
            title: Strings.addMoney,
            action: () {
              _bottomSlideWidget(context);
            }));
  }

  Future _bottomSlideWidget(context) {
    return showModalBottomSheet(
      isScrollControlled: true,
      context: context,
      builder: (context) {
        return Container(
            padding: EdgeInsets.all(Dimensions.paddingSize),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(15),
            ),
            child: Column(
              mainAxisSize: mainMin,
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
                TitleHeading2Widget(
                  text: Strings.preview,
                  fontSize: Dimensions.headingTextSize6 * 2,
                ),
                Padding(
                  padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
                  child: TitleHeadingRow(
                      leftHeading: Strings.requestAmount,
                      rightHeading:
                          "${(double.tryParse(amountController.amountController.text) ?? 0.0).toStringAsFixed(4)} ${LocalStorage.baseCurrencyCode}"),
                ),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                TitleHeadingRow(
                    leftHeading: Strings.exchangeRate,
                    rightHeading:
                        " 1.0000 ${LocalStorage.baseCurrencyCode} = ${controller.gatewayRate.value.toStringAsFixed(4)} ${controller.currencyCode.value}"),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                TitleHeadingRow(
                    leftHeading: Strings.fees,
                    rightHeading:
                        "${controller.getFees().toStringAsFixed(4)} ${LocalStorage.baseCurrencyCode}"),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                TitleHeadingRow(
                    leftHeading: Strings.totalPayable,
                    rightHeading:
                        "${controller.getTotalPayable().toStringAsFixed(3)} ${controller.currencyCode.value}"),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                TitleHeadingRow(
                    leftHeading: Strings.willGet,
                    rightHeading:
                        "${controller.requestAmount.value.toStringAsFixed(4)} ${LocalStorage.baseCurrencyCode}"),
                Flexible(
                  fit: FlexFit.loose,
                  child: _buttonWidget(context),
                ),
              ],
            ));
      },
    );
  }

  Padding _buttonWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
      child: Column(
        mainAxisSize: mainMin,
        children: [
          Obx(
            () => controller.isWebViewLoading ||
                    controller.isInsertLoading ||
                    controller.isTatumProcessLoading
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: Strings.confirm,
                    fontWeight: FontWeight.w600,
                    onPressed: () {
                      controller.processPayment();
                    },
                    buttonTextColor: CustomColor.whiteColor,
                    buttonColor: CustomColor.primaryLightColor,
                    elevation: 0,
                    borderColor: Theme.of(context).primaryColor,
                    borderWidth: 1.5,
                    radius: Dimensions.radius * 1.2,
                    height: Dimensions.heightSize * 4.67,
                  ),
          ),
        ],
      ),
    );
  }
}
