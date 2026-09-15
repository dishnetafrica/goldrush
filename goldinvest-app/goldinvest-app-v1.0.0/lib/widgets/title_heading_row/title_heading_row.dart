import 'package:flutter/material.dart';

import '../../utils/custom_color.dart';
import '../common/text_labels/title_heading4_widget.dart';

class TitleHeadingRow extends StatelessWidget {
  final String leftHeading;
  final String rightHeading;
  final MainAxisAlignment mainSpaceBet;

  const TitleHeadingRow({
    super.key,
    required this.leftHeading,
    required this.rightHeading,
    this.mainSpaceBet = MainAxisAlignment.spaceBetween,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: mainSpaceBet,
      children: [
        TitleHeading4Widget(
          text: leftHeading,
          color: CustomColor.liteBlack2,
        ),
        TitleHeading4Widget(
          text: rightHeading,
          fontWeight: FontWeight.w700,
          color: CustomColor.primaryLightTextColor,
        ),
      ],
    );
  }
}
