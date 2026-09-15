import 'package:flutter/material.dart';
import 'package:goldinvest/custom_assets/assets.gen.dart';
import 'package:goldinvest/utils/custom_color.dart';
import 'package:goldinvest/utils/dimensions.dart';
import 'package:goldinvest/utils/size.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading5_widget.dart';

import '../common/others/custom_image_widget.dart';

class CustomRowWidget extends StatelessWidget {
  final String text;
  final String? iconPath;
  final dynamic color;
  const CustomRowWidget({
    super.key,
    required this.text,
    this.iconPath,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.1),
      child: Row(
        mainAxisAlignment: mainStart,
        children: [
          CustomImageWidget(
            path: Assets.icon.tickIcon3,
            color: color,
          ),
          Padding(
              padding: EdgeInsets.only(left: Dimensions.paddingSize * 0.2),
              child: TitleHeading5Widget(
                color: CustomColor.liteBlack1,
                text: text,
              )),
        ],
      ),
    );
  }
}
