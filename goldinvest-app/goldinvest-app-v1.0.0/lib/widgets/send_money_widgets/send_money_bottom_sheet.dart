import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/utils/custom_snackbar.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../controller/send_money-controller/send_money_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/buttons/primary_button.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading2_widget.dart';
import '../common/text_labels/title_heading4_widget.dart';
import '../custom_slider/custom_slider.dart';
import '../slider_button_widget/slider_button.dart';

class SendMoneyBottomSheet extends StatelessWidget {
  SendMoneyBottomSheet({super.key});
  final SendMoneyController controller = Get.put(SendMoneyController());

  @override
  Widget build(BuildContext context) {
    final h = MediaQuery.of(context).size.height;
    return Padding(
        padding: EdgeInsets.only(top: h / 6),
        child: SlideButtonWidget(
            title: Strings.sendMoney,
            action: () {
              controller.getFees();
              controller.getTotalPayable();
              Future.delayed(const Duration(milliseconds: 300), () {
                _bottomSlideWidget(context);
              });
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
                ),
                verticalSpace(Dimensions.paddingSize),
                _buildTitleHeadingRow(
                    leftHeading: Strings.sendingAmount,
                    rightHeading:
                        "${controller.sendMoneyAmount.value} ${LocalStorage.baseCurrencyCode}"),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                _buildTitleHeadingRow(
                    leftHeading: Strings.exchangeRate,
                    rightHeading:
                        " 1 ${LocalStorage.baseCurrencyCode} = 1 ${LocalStorage.baseCurrencyCode}"),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                _buildTitleHeadingRow(
                    leftHeading: Strings.fees,
                    rightHeading:
                        "${controller.getFees().value.toStringAsFixed(4)} ${LocalStorage.baseCurrencyCode}"),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                _buildTitleHeadingRow(
                    leftHeading: Strings.recipientsWillGet,
                    rightHeading:
                        "${controller.sendMoneyAmount.value} ${LocalStorage.baseCurrencyCode}"),
                Divider(
                  height: Dimensions.heightSize * 2.5,
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  thickness: 1,
                ),
                _buildTitleHeadingRow(
                    leftHeading: Strings.payInTotal,
                    rightHeading:
                        "${controller.getTotalPayable().value} ${LocalStorage.baseCurrencyCode}"),
                _buttonWidget(context),
              ],
            ));
      },
    ).whenComplete(() {
      SlideToActButtonController.resetSlider;
    });
  }

  Padding _buttonWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.heightSize * 0.5),
      child: Column(
        children: [
          Obx(
            () => controller.isLoadingOnSendMoney
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: Strings.confirm,
                    fontWeight: FontWeight.w600,
                    onPressed: () {
                      if (controller.receiverEmail.text.isNotEmpty) {
                        controller.onSendMoney();
                      } else {
                        CustomSnackBar.error(
                          DynamicLanguage.key(Strings.receiverEmailAddress),
                        );
                      }
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
