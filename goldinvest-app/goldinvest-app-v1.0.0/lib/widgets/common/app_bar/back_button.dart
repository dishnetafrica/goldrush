import 'package:flutter/material.dart';

import '../../../custom_assets/assets.gen.dart';
import '../../../utils/dimensions.dart';
import '../others/custom_image_widget.dart';

class BackButtonWidget extends StatelessWidget {
  const BackButtonWidget({
    super.key,
    required this.onTap,
    this.padding,
  });

  final VoidCallback onTap;
  final EdgeInsetsGeometry? padding;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      hoverColor: Colors.transparent,
      splashColor: Colors.transparent,
      highlightColor: Colors.transparent,
      onTap: onTap,
      child: Padding(
        padding: padding ??
            EdgeInsets.all(Dimensions.paddingSize *
                0.5), // Use the nullable padding or default
        child: CustomImageWidget(
          path: Assets.icon.left,
          height: Dimensions.heightSize * 2,
          width: Dimensions.widthSize * 2,
        ),
      ),
    );
  }
}
