import 'package:flutter/material.dart';
import 'package:goldinvest/custom_assets/assets.gen.dart';

import 'package:goldinvest/utils/custom_color.dart';
import 'package:goldinvest/utils/dimensions.dart';
import 'package:goldinvest/widgets/common/others/custom_image_widget.dart';

class AppVersion extends StatelessWidget {
  const AppVersion({super.key});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: EdgeInsets.only(bottom: Dimensions.buttonHeight * 0.2),
          child: CustomImageWidget(
            path: Assets.icon.goldInvest,
            color: CustomColor.whiteColor,
          ),
        ),
      ],
    );
  }
}
