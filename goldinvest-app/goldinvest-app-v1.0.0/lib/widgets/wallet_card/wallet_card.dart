import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../controller/logs/logs_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading3_widget.dart';
import '../common/text_labels/title_heading4_widget.dart';
import '../common/text_labels/title_heading5_widget.dart';

class CustomWalletCard extends StatelessWidget {
  final controller = Get.put(LogsController());

  final String? logoPath;
  final String walletTitle;
  final String? walletSubTitle;
  final String? amount;
  final String? balanceText;
  final FontWeight? fontWeightTitle;
  final int? status;
  final bool? isSelected;
  final Color? fontColorTitle;
  final FontWeight? fontWeightTitleMain;
  final FontWeight? fontWeightSubTitleMain;
  final Color? fontColorSubtitle;
  final double? fontSizeTitle;
  final double? fontSizeSubtitle;
  final Widget? customWidget;
  final Widget? statusWidget;

  final Color? fontColorBalance;
  final double? logoSizeHeight;
  final double? logoSizeWeight;
  final Color? statusColor;
  final String? statusText;

  CustomWalletCard(
      {super.key,
      this.logoPath,
      required this.walletTitle,
      this.amount,
      this.balanceText,
      this.isSelected,
      this.status,
      this.fontWeightTitle,
      this.walletSubTitle,
      this.fontColorTitle,
      this.fontColorSubtitle,
      this.fontSizeTitle,
      this.fontSizeSubtitle,
      this.fontColorBalance,
      this.logoSizeHeight,
      this.logoSizeWeight,
      this.fontWeightTitleMain,
      this.fontWeightSubTitleMain,
      this.statusColor,
      this.statusText,
      this.statusWidget,
      this.customWidget});

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisAlignment: mainCenter,
      children: [
        SizedBox(
          height: Dimensions.heightSize * 6.67,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                mainAxisAlignment: mainCenter,
                children: [
                  Row(
                    children: [
                      customWidget ??
                          Container(
                            decoration: BoxDecoration(
                              borderRadius:
                                  BorderRadius.circular(Dimensions.radius),
                              color: CustomColor.primaryLightTextColor
                                  .withValues(alpha: 0.1),
                            ),
                            child: CustomImageWidget(
                              path: logoPath ?? " ",
                              height: logoSizeHeight ??
                                  Dimensions.heightSize * 4.67,
                              width:
                                  logoSizeWeight ?? Dimensions.widthSize * 5.6,
                            ),
                          ),
                      Padding(
                        padding: EdgeInsets.only(
                          left: Dimensions.paddingSize * 0.4,
                        ),
                        child: Column(
                          mainAxisAlignment: mainCenter,
                          crossAxisAlignment: crossStart,
                          children: [
                            Row(
                              children: [
                                TitleHeading3Widget(
                                  text: walletTitle,
                                  fontWeight:
                                      fontWeightTitle ?? FontWeight.w500,
                                  fontSize: fontSizeTitle ??
                                      Dimensions.headingTextSize3,
                                  color: fontColorTitle ??
                                      CustomColor.primaryLightTextColor,
                                ),
                                statusWidget ??
                                    Padding(
                                      padding: EdgeInsets.only(
                                          left: Dimensions.paddingSize * 0.1),
                                      child: isSelected == true
                                          ? Container(
                                              alignment: Alignment.center,
                                              decoration: BoxDecoration(
                                                  borderRadius:
                                                      BorderRadius.all(
                                                          Radius.circular(
                                                              Dimensions
                                                                      .radius *
                                                                  0.2)),
                                                  color: statusColor ??
                                                      _getStatusColor(
                                                          status ?? 1)),
                                              padding: const EdgeInsets.all(5),
                                              child: Center(
                                                  child: TitleHeading5Widget(
                                                      text: statusText ??
                                                          _getStatusText(
                                                              status ?? 1),
                                                      fontWeight:
                                                          FontWeight.w700,
                                                      fontSize: Dimensions
                                                              .headingTextSize6 *
                                                          0.9,
                                                      color: CustomColor
                                                          .whiteColor)),
                                            )
                                          : null,
                                    ),
                              ],
                            ),
                            walletSubTitle != null
                                ? TitleHeading3Widget(
                                    text: walletSubTitle!,
                                    fontWeight: fontWeightSubTitleMain ??
                                        FontWeight.w500,
                                    fontSize: fontSizeSubtitle ??
                                        Dimensions.headingTextSize3,
                                    color: fontColorSubtitle ??
                                        CustomColor.liteBlack2,
                                  )
                                : Padding(
                                    padding: EdgeInsets.only(
                                        left: Dimensions.paddingSize * 0.1),
                                    child: isSelected == true
                                        ? Container(
                                            alignment: Alignment.center,
                                            decoration: BoxDecoration(
                                              borderRadius: BorderRadius.all(
                                                Radius.circular(
                                                    Dimensions.radius * 0.2),
                                              ),
                                              color: statusColor ??
                                                  _getStatusColor(controller
                                                      .orders
                                                      .first
                                                      .orderStatus),
                                            ),
                                            padding: const EdgeInsets.all(5),
                                            child: Center(
                                              child: TitleHeading5Widget(
                                                text: statusText ??
                                                    _getStatusText(controller
                                                        .orders
                                                        .first
                                                        .orderStatus),
                                                fontWeight: FontWeight.w700,
                                                fontSize: Dimensions
                                                        .headingTextSize6 *
                                                    0.7,
                                                color: CustomColor.whiteColor,
                                              ),
                                            ),
                                          )
                                        : null,
                                  ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              Column(
                mainAxisAlignment: mainCenter,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  TitleHeading4Widget(
                    color: CustomColor.primaryLightTextColor,
                    text: amount ?? " ",
                    fontWeight: FontWeight.w700,
                  ),
                  TitleHeading5Widget(
                    text: balanceText ?? " ",
                    color: CustomColor.liteBlack2,
                  ),
                ],
              ),
            ],
          ),
        ),
        Divider(
          height: Dimensions.heightSize * 0.5,
          color: CustomColor.blackColor.withValues(alpha: 0.05),
          thickness: 1,
        ),
      ],
    );
  }
}

Color _getStatusColor(int status) {
  switch (status) {
    case 1:
      return CustomColor.orangeColor.withValues(alpha: 0.9);
    case 2:
      return CustomColor.blueColor.withValues(alpha: 0.9);
    case 3:
      return CustomColor.greenColor.withValues(alpha: 0.9);
    default:
      return CustomColor.redColor;
  }
}

String _getStatusText(int status) {
  switch (status) {
    case 1:
      return Strings.accepted;
    case 2:
      return Strings.ongoing;
    case 3:
      return Strings.delivered;
    default:
      return Strings.cencel;
  }
}
