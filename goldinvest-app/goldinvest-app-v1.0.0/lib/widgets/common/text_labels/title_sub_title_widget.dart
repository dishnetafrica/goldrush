import 'package:flutter/material.dart';

import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/size.dart';
import 'title_heading1_widget.dart';
import 'title_heading4_widget.dart';

class TitleSubTitleWidget extends StatelessWidget {
  const TitleSubTitleWidget({
    super.key,
    required this.title,
    required this.subTitle,
    this.textOverflow,
    this.subTitleFontSize,
    this.titleSize,
    this.titleColor,
    this.subTitleColor,
    this.isCenterText = false,
  });
  final String title, subTitle;
  final double? subTitleFontSize;
  final double? titleSize;
  final Color? titleColor, subTitleColor;
  final bool isCenterText;
  final TextOverflow? textOverflow;
  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: isCenterText ? crossCenter : crossStart,
      mainAxisAlignment: isCenterText ? mainCenter : mainCenter,
      children: [
        TitleHeading1Widget(
          text: title,
          color: titleColor ?? CustomColor.blackTextColor,
          fontWeight: FontWeight.w800,
          fontSize: titleSize ?? Dimensions.headingTextSize1,
          textAlign: isCenterText ? TextAlign.center : TextAlign.start,
        ),
        verticalSpace(Dimensions.marginBetweenInputTitleAndBox),
        Visibility(
          visible: subTitle != '',
          child: TitleHeading4Widget(
            text: subTitle,
            color: subTitleColor ?? CustomColor.liteBlack2,
            fontWeight: FontWeight.w500,
            fontSize: subTitleFontSize ?? Dimensions.headingTextSize3,
            textAlign: isCenterText ? TextAlign.center : TextAlign.start,
            textOverflow: textOverflow,
          ),
        ),
      ],
    );
  }
}
