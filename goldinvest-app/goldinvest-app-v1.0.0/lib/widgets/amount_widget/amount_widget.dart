import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../controller/increment_decrement/amount_controller_money_out.dart';
import '../../controller/increment_decrement/amount_controller_add_money.dart';
import '../../controller/increment_decrement/amount_controller_send_money.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/custom_style.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading3_widget.dart';

// ignore: must_be_immutable
class AmountDesign extends StatelessWidget {
  final GetxController? counterController;
  final double? onAmountChanged;

  const AmountDesign({
    super.key,
    this.onAmountChanged,
    this.counterController,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          margin: EdgeInsets.symmetric(
            vertical: Dimensions.marginSizeVertical * 0.5,
          ),
          padding: EdgeInsets.all(
            Dimensions.paddingSize * 0.4,
          ),
          decoration: ShapeDecoration(
            color: CustomColor.blackColor.withValues(alpha: 0.05),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(Dimensions.radius * 1.6),
            ),
          ),
          child: Column(
            children: [
              TitleHeading3Widget(
                text: DynamicLanguage.isLoading
                    ? ""
                    : DynamicLanguage.key(Strings.amountUsd),
                fontWeight: FontWeight.w400,
                opacity: 0.6,
              ),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  horizontalSpace(Dimensions.widthSize),
                  GestureDetector(
                    onTap: () {
                      // Check and call decreaseAmount on the appropriate controller
                      if (counterController is AmountControllerAddMoney) {
                        (counterController as AmountControllerAddMoney)
                            .decreaseAmount();
                      } else if (counterController
                          is AmountControllerSendMoney) {
                        (counterController as AmountControllerSendMoney)
                            .decreaseAmount();
                      } else if (counterController
                          is AmountControllerMoneyOut) {
                        (counterController as AmountControllerMoneyOut)
                            .decreaseAmount();
                      }
                    },
                    child: CircleAvatar(
                      backgroundColor: CustomColor.whiteColor,
                      child: CustomImageWidget(path: Assets.icon.minus),
                    ),
                  ),
                  horizontalSpace(Dimensions.widthSize * 2),
                  Expanded(
                    child: TextField(
                      cursorColor: CustomColor.primaryLightColor,
                      onChanged: (value) {
                        double.tryParse(value);
                      },
                      textAlign: TextAlign.center,
                      controller: (counterController
                              is AmountControllerAddMoney)
                          ? (counterController as AmountControllerAddMoney)
                              .amountController
                          : (counterController is AmountControllerSendMoney)
                              ? (counterController as AmountControllerSendMoney)
                                  .amountController
                              : (counterController is AmountControllerMoneyOut)
                                  ? (counterController
                                          as AmountControllerMoneyOut)
                                      .amountController
                                  : null,
                      style: Get.isDarkMode
                          ? CustomStyle.darkHeading1TextStyle.copyWith(
                              fontSize: Dimensions.headingTextSize1 * 2,
                            )
                          : CustomStyle.lightHeading1TextStyle.copyWith(
                              fontSize: Dimensions.headingTextSize1 * 2,
                            ),
                      inputFormatters: <TextInputFormatter>[
                        FilteringTextInputFormatter.allow(
                            RegExp(r'^\d*\.?\d*')),
                      ],
                      decoration: const InputDecoration(
                        enabledBorder: InputBorder.none,
                        focusedBorder: InputBorder.none,
                        focusedErrorBorder: InputBorder.none,
                        hintText: '0',
                      ),
                    ),
                  ),
                  horizontalSpace(Dimensions.widthSize * 2),
                  GestureDetector(
                    onTap: () {
                      // Check and call increaseAmount on the appropriate controller
                      if (counterController is AmountControllerAddMoney) {
                        (counterController as AmountControllerAddMoney)
                            .increaseAmount();
                      } else if (counterController
                          is AmountControllerSendMoney) {
                        (counterController as AmountControllerSendMoney)
                            .increaseAmount();
                      } else if (counterController
                          is AmountControllerMoneyOut) {
                        (counterController as AmountControllerMoneyOut)
                            .increaseAmount();
                      }
                    },
                    child: CircleAvatar(
                      backgroundColor: CustomColor.whiteColor,
                      child: CustomImageWidget(path: Assets.icon.add),
                    ),
                  ),
                  horizontalSpace(Dimensions.widthSize),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }
}
