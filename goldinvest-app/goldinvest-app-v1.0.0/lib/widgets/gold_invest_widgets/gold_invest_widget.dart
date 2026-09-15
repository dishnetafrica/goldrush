import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/utils/custom_snackbar.dart';
import 'package:goldinvest/widgets/custom_agree/custom_agree_widget.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../controller/invest/invest_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../widgets/common/inputs/primary_input_widget.dart';
import '../../widgets/common/others/custom_image_widget.dart';
import '../../widgets/common/text_labels/title_heading2_widget.dart';
import '../../widgets/common/text_labels/title_heading3_widget.dart';
import '../../widgets/title_heading_row/title_heading_row.dart';
import '../common/buttons/primary_button.dart';

class GoldInvestWidget extends StatelessWidget {
  GoldInvestWidget({super.key});
  final controller = Get.put(InvestPlanController());

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
      child: ListView(
        shrinkWrap: true,
        children: [
          Container(
            alignment: Alignment.center,
            child: CustomImageWidget(
              path: Assets.icon.slideBarRectangle,
              width: Dimensions.widthSize * 4,
              height: Dimensions.heightSize * 0.5,
            ),
          ),
          Padding(
            padding: EdgeInsets.only(
                top: Dimensions.paddingSize * 0.5,
                left: Dimensions.paddingSize,
                right: Dimensions.paddingSize),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const TitleHeading2Widget(
                  text: Strings.purchasePlan,
                  color: CustomColor.blackColor,
                ),
                verticalSpace(Dimensions.paddingSize),
                TitleHeadingRow(
                  leftHeading: Strings.plan,
                  rightHeading: controller.selectPlan.value,
                ),
                Divider(
                  height: Dimensions.heightSize * 2,
                  color: CustomColor.blackColor.withValues(alpha: 0.05),
                  thickness: 1,
                ),
                TitleHeadingRow(
                  leftHeading: Strings.duration,
                  rightHeading:
                      '${controller.duration.value} ${DynamicLanguage.key(Strings.days)}',
                ),
                Divider(
                  height: Dimensions.heightSize * 2,
                  color: CustomColor.blackColor.withValues(alpha: 0.05),
                  thickness: 1,
                ),
                TitleHeadingRow(
                  leftHeading: Strings.maximumInvestment,
                  rightHeading:
                      '${controller.maximumInvestAmount.value} ${LocalStorage.baseCurrencyCode}',
                ),
                Divider(
                  height: Dimensions.heightSize * 2,
                  color: CustomColor.blackColor.withValues(alpha: 0.05),
                  thickness: 1,
                ),
                TitleHeadingRow(
                  leftHeading: Strings.fixedProfit,
                  rightHeading:
                      '${controller.fixedProfit.value} ${LocalStorage.baseCurrencyCode}',
                ),
                Divider(
                  height: Dimensions.heightSize * 2,
                  color: CustomColor.blackColor.withValues(alpha: 0.05),
                  thickness: 1,
                ),
                TitleHeadingRow(
                  leftHeading: Strings.percentProfit,
                  rightHeading: "${controller.percentProfit.value} %",
                ),
                verticalSpace(Dimensions.widthSize * 2),
                const TitleHeading3Widget(
                  text: Strings.investAmount,
                  color: CustomColor.liteBlack1,
                ),
                PrimaryInputWidget(
                  textController: controller.investAmount,
                  hintText: '0.0',
                  textInputType: TextInputType.number,
                ),
                verticalSpace(Dimensions.heightSize * 2),
                _agreedWidget(context),
                _bottomButtonWidget(context),
                verticalSpace(Dimensions.heightSize * 2),
              ],
            ),
          ),
        ],
      ),
    );
  }

  AgreedWidget _agreedWidget(BuildContext context) {
    return const AgreedWidget(
      isSelected: false,
      agreedText: Strings.iHaveAgreed,
      termsText: Strings.termAndCondition,
    );
  }

  Padding _bottomButtonWidget(context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize),
      child: Row(
        mainAxisAlignment: mainSpaceBet,
        children: [
          SizedBox(
              width: Dimensions.widthSize * 15,
              child: PrimaryButton(
                buttonColor: CustomColor.whiteColor,
                title: Strings.cancel,
                buttonTextColor: CustomColor.primaryLightColor,
                borderWidth: 2,
                onPressed: () {
                  Get.close(1);
                  controller.investAmount.clear();
                },
              )),
          SizedBox(
            width: Dimensions.widthSize * 15,
            child: Obx(
              () => controller.isLoadingPlan
                  ? const CustomLoadingAPI()
                  : PrimaryButton(
                      title: Strings.purchase,
                      buttonTextColor: CustomColor.whiteColor,
                      onPressed: () {
                        if (controller.isSelected.value == true) {
                          controller.onPlanPurchase();
                        } else {
                          CustomSnackBar.error(
                              DynamicLanguage.key(Strings.pleaseAgree));
                        }
                      }),
            ),
          )
        ],
      ),
    );
  }
}
