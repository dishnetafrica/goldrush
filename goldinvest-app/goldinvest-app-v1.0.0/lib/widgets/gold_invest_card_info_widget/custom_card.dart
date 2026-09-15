import 'package:carousel_slider/carousel_slider.dart';
import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/utils/size.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading2_widget.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading3_widget.dart';

import '../../controller/invest/invest_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../dashboard_top_design_widget/clip_path.dart';
import '../../widgets/common/text_labels/title_heading4_widget.dart';
import '../../widgets/gold_invest_card_info_widget/gold_invest_card_info_widget.dart';

// ignore: must_be_immutable
class CustomCardGoldPan extends StatelessWidget {
  CustomCardGoldPan({super.key});

  final controller = Get.put(InvestPlanController());
  List<Color> colorPairs = [
    CustomColor.primaryLightColor,
    CustomColor.greenColor,
    CustomColor.blueColor
  ];

  Color getColorForIndex(int index) {
    return colorPairs[index % colorPairs.length];
  }

  @override
  Widget build(BuildContext context) {
    final h = MediaQuery.of(context).size.height;
    return CarouselSlider.builder(
      options: CarouselOptions(
          onPageChanged: (index, reason) => controller.setSelectedIndex(index),
          height: h * 0.58,
          enlargeCenterPage: true),
      itemCount: controller.plansList.length,
      itemBuilder: (context, index, realIndex) {
        if (controller.plansList.isEmpty) {
          return Center(
            child: Text(
              DynamicLanguage.key(Strings.noDataFound),
              style: TextStyle(
                  fontSize: Dimensions.headingTextSize4,
                  color: CustomColor.primaryLightTextColor,
                  fontWeight: FontWeight.w700),
            ),
          );
        }
        Color cardColor = getColorForIndex(index);
        var data = controller.plansList[index];

        return Padding(
          padding: EdgeInsets.symmetric(vertical: h * 0.02),
          child: Container(
            decoration: BoxDecoration(
                boxShadow: [
                  BoxShadow(
                    color: CustomColor.blackColor.withValues(alpha: 0.1),
                    blurRadius: 20.0,
                    spreadRadius: 2.0,
                    offset: const Offset(0, 4),
                  ),
                ],
                color: CustomColor.whiteColor,
                borderRadius: BorderRadius.circular(Dimensions.radius * 2)),
            child: Stack(
              children: [
                Stack(
                  children: [
                    ClipPath(
                      clipper: OvalBottomBorderClipper(),
                      child: Container(
                        decoration: BoxDecoration(
                            color: cardColor,
                            borderRadius:
                                BorderRadius.circular(Dimensions.radius * 2)),
                        height: Dimensions.heightSize * 11.25,
                        width: Dimensions.widthSize * 32,
                        child: Padding(
                          padding:
                              EdgeInsets.only(bottom: Dimensions.paddingSize),
                          child: Column(
                            mainAxisAlignment: mainCenter,
                            children: [
                              TitleHeading2Widget(
                                text: data.name,
                                color: CustomColor.whiteColor,
                              ),
                              TitleHeading2Widget(
                                fontSize: Dimensions.headingTextSize2 * 0.5,
                                text:
                                    '${DynamicLanguage.key(Strings.profitReturnType)} ${data.profitReturnType}',
                                color: CustomColor.whiteColor,
                              )
                            ],
                          ),
                        ),
                      ),
                    ),
                    Positioned(
                        bottom: -3,
                        left: 37,
                        child: Container(
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: CustomColor.whiteColor,
                              width: 3.0,
                            ),
                          ),
                          child: CircleAvatar(
                            backgroundColor: cardColor,
                            radius: Dimensions.radius * 1.8,
                          ),
                        )),
                    Positioned(
                        bottom: -3,
                        right: 37,
                        child: Container(
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: CustomColor.whiteColor,
                              width: 3.0,
                            ),
                          ),
                          child: CircleAvatar(
                            backgroundColor: cardColor,
                            radius: Dimensions.radius * 1.8,
                          ),
                        )),
                  ],
                ),
                Positioned(
                    top: Dimensions.heightSize * 7.5,
                    left: Dimensions.widthSize * 9.5,
                    child: Container(
                      decoration: BoxDecoration(
                        color: CustomColor.whiteColor,
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: cardColor.withValues(alpha: 0.1),
                            blurRadius: 40.0,
                            spreadRadius: 70.0,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: CircleAvatar(
                        radius: Dimensions.radius * 4.8,
                        backgroundColor: CustomColor.whiteColor,
                        child: Column(
                          mainAxisAlignment: mainCenter,
                          children: [
                            TitleHeading3Widget(
                              text:
                                  '\$ ${data.minimumInvestmentOffer == null ? (data.minimumInvestment.toStringAsFixed(2)) : (data.minimumInvestmentOffer as num).toStringAsFixed(2)}',
                              fontWeight: FontWeight.w700,
                              fontSize: Dimensions.headingTextSize3 * 1.11,
                            ),
                            data.minimumInvestmentOffer == null
                                ? const SizedBox.shrink()
                                : TitleHeading4Widget(
                                    text:
                                        ' ${data.minimumInvestmentOffer == null ? " " : (data.minimumInvestment.toStringAsFixed(2))}',
                                    fontWeight: FontWeight.w700,
                                    fontSize: Dimensions.headingTextSize6,
                                    color: CustomColor.liteBlack2,
                                  )
                          ],
                        ),
                      ),
                    )),
                Positioned(
                    top: Dimensions.heightSize * 11.25,
                    right: Dimensions.widthSize * 2,
                    child: CircleAvatar(
                      backgroundColor: cardColor,
                      radius: Dimensions.radius * 0.5,
                    )),
                Positioned(
                    top: Dimensions.heightSize * 12.5,
                    left: Dimensions.widthSize * 6.5,
                    child: CircleAvatar(
                      backgroundColor: cardColor,
                      radius: Dimensions.radius * 0.8,
                    )),
                Positioned(
                  top: Dimensions.heightSize * 17.5,
                  left: Dimensions.widthSize * 4.5,
                  child: Column(
                    crossAxisAlignment: crossStart,
                    children: [
                      CustomRowWidget(
                          color: cardColor,
                          text:
                              '${DynamicLanguage.key(Strings.planDuration)} ${data.planDuration.toStringAsFixed(0)} Days'),
                      CustomRowWidget(
                          color: cardColor,
                          text:
                              '${DynamicLanguage.key(Strings.maximumInvestAmount)} \$ ${data.maximumInvestment.toStringAsFixed(2)}'),
                      CustomRowWidget(
                          color: cardColor,
                          text:
                              '${DynamicLanguage.key(Strings.fixed)} \$ ${data.profit.toStringAsFixed(2)}'),
                      CustomRowWidget(
                          color: cardColor,
                          text:
                              '${DynamicLanguage.key(Strings.percentage)} ${data.profitPercentage.toString()} %'),
                      CustomRowWidget(
                          color: cardColor, text: Strings.justClick),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
