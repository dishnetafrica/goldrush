import 'package:flutter/material.dart';
import 'package:goldinvest/utils/size.dart';

import '../common/text_labels/title_heading4_widget.dart';

class ItemBuilderWidget extends StatelessWidget {
  final String left;
  final String right;

  const ItemBuilderWidget({
    super.key,
    required this.left,
    required this.right,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: mainSpaceBet,
      children: [
        TitleHeading4Widget(
          text: left,
          fontWeight: FontWeight.w400,
        ),
        TitleHeading4Widget(
          text: right,
          fontWeight: FontWeight.w700,
        ),
      ],
    );
  }
}
