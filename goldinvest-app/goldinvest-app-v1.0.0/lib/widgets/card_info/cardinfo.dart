import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';

import '../../custom_assets/assets.gen.dart';
import '../../utils/dimensions.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading5_widget.dart';

class CustomTitleRow extends StatelessWidget {
  final String title;
  final Color? tickColor;
  final Color? textColor;

  const CustomTitleRow(
      {super.key,
      required this.title,
      required this.tickColor,
      required this.textColor});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.2),
      child: Row(
        children: [
          CustomImageWidget(path: Assets.icon.designTick, color: tickColor),
          SizedBox(width: Dimensions.widthSize * 0.5),
          Flexible(
            child: TitleHeading5Widget(
              text: DynamicLanguage.isLoading ? "" : DynamicLanguage.key(title),
              color: textColor,
              textOverflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}
