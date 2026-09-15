import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/controller/my_investment/my_investment_controller.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading2_widget.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading4_widget.dart';
import 'package:intl/intl.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';

import '../../utils/custom_color.dart';

import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../widgets/common/others/custom_image_widget.dart';
import '../../widgets/common/text_labels/title_heading5_widget.dart';
import '../../widgets/title_heading_row/title_heading_row.dart';

class MyInvestWidgets extends StatelessWidget {
  final dateOnly = DateFormat('dd');
  final monthOnly = DateFormat('MMM');
  final yearOnly = DateFormat('yyyy');
  final int index;
  MyInvestWidgets({super.key, required this.index});
  final MyInvestmentController controller = Get.put(MyInvestmentController());

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      child: Padding(
        padding: EdgeInsets.only(
          top: Dimensions.paddingSize,
          //bottom: MediaQuery.of(context).viewInsets.bottom,
        ),
        child: Column(
          children: [
            Container(
              alignment: Alignment.center,
              child: CustomImageWidget(
                path: Assets.icon.slideBarRectangle,
                width: Dimensions.widthSize * 4,
                height: Dimensions.heightSize * 0.5,
              ),
            ),
            Expanded(
              child: Padding(
                padding: EdgeInsets.all(Dimensions.paddingSize),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    TitleHeading2Widget(
                      text: controller.investments![index].investPlan.name,
                      color: CustomColor.blackColor,
                    ),
                    TitleHeading4Widget(
                        text:
                            "${dateOnly.format(controller.investments![index].expAt)} ${monthOnly.format(controller.investments![index].expAt)} ${yearOnly.format(controller.investments![index].expAt)}"),
                    verticalSpace(Dimensions.paddingSize),
                    TitleHeadingRow(
                      leftHeading: Strings.duration,
                      rightHeading:
                          "${controller.investments![index].investPlan.planDuration.toString()} ${DynamicLanguage.key(Strings.days)}",
                    ),
                    Divider(
                      height: Dimensions.heightSize * 1.5,
                      color: CustomColor.blackColor.withValues(alpha: 0.05),
                      thickness: 1,
                    ),
                    TitleHeadingRow(
                      leftHeading: Strings.investment,
                      rightHeading:
                          "${controller.investments![index].investAmount.toString()} ${LocalStorage.baseCurrencyCode}",
                    ),
                    Divider(
                      height: Dimensions.heightSize * 1.5,
                      color: CustomColor.blackColor.withValues(alpha: 0.05),
                      thickness: 1,
                    ),
                    TitleHeadingRow(
                      leftHeading: Strings.profitPercent,
                      rightHeading:
                          '${controller.investments![index].investPlan.profitPercentage.toString()}%',
                    ),
                    Divider(
                      height: Dimensions.heightSize * 1.5,
                      color: CustomColor.blackColor.withValues(alpha: 0.05),
                      thickness: 1,
                    ),
                    TitleHeadingRow(
                      leftHeading: Strings.fixedProfit,
                      rightHeading:
                          '${controller.investments![index].investPlan.profit.toString()} ${LocalStorage.baseCurrencyCode}',
                    ),
                    Divider(
                      height: Dimensions.heightSize * 1.5,
                      color: CustomColor.blackColor.withValues(alpha: 0.05),
                      thickness: 1,
                    ),
                    TitleHeadingRow(
                      leftHeading: Strings.currentBalance,
                      rightHeading:
                          "${controller.investments![index].availableBalance} ${LocalStorage.baseCurrencyCode} ",
                    ),
                    Divider(
                      height: Dimensions.heightSize * 1.5,
                      color: CustomColor.blackColor.withValues(alpha: 0.05),
                      thickness: 1,
                    ),
                    TitleHeadingRow(
                      leftHeading: Strings.profitReturnType,
                      rightHeading: controller
                          .investments![index].investPlan.profitReturnType,
                    ),
                    Divider(
                      height: Dimensions.heightSize * 1.5,
                      color: CustomColor.blackColor.withValues(alpha: 0.05),
                      thickness: 1,
                    ),
                    Row(
                      mainAxisAlignment: mainSpaceBet,
                      children: [
                        const TitleHeading4Widget(text: Strings.status),
                        Container(
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                              color: CustomColor.greenColor,
                              borderRadius: BorderRadius.circular(
                                  Dimensions.radius * 0.1)),
                          width: Dimensions.widthSize * 5.5,
                          height: Dimensions.heightSize * 1.42,
                          child: const TitleHeading5Widget(
                            text: Strings.success,
                            color: CustomColor.whiteColor,
                            fontWeight: FontWeight.w600,
                          ),
                        )
                      ],
                    )
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
