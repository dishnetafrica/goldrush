import 'package:flutter/material.dart';
import 'package:goldinvest/utils/size.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading3_widget.dart';
import '../common/text_labels/title_heading4_widget.dart';
import '../common/text_labels/title_heading5_widget.dart';

class WalletInfoWidget extends StatelessWidget {
  final String imagePath;
  final String title;
  final String? balance;
  final String? balanceDescription;
  final Color? backgroundColor;
  final Color? foregroundColor;
  final Color? textColor;

  const WalletInfoWidget({
    super.key,
    this.backgroundColor,
    this.foregroundColor,
    required this.imagePath,
    required this.title,
    this.balance, // Nullable field
    this.balanceDescription,
    this.textColor, // Nullable field
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: backgroundColor ?? CustomColor.whiteColor.withValues(alpha: 0.9),
        borderRadius: BorderRadius.circular(Dimensions.radius),
      ),
      height: Dimensions.heightSize * 6.67,
      child: Row(
        mainAxisAlignment: mainSpaceBet,
        children: [
          Row(
            children: [
              Padding(
                padding: EdgeInsets.only(left: Dimensions.paddingSize * 0.5),
                child: Container(
                  height: Dimensions.widthSize * 5.6,
                  width: Dimensions.heightSize * 4.67,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(Dimensions.radius),
                    color: foregroundColor ?? CustomColor.whiteColor,
                  ),
                  child: Padding(
                    padding: EdgeInsets.all(Dimensions.paddingSize * 0.5),
                    child: CustomImageWidget(
                      path: imagePath,
                      height: Dimensions.heightSize * 2,
                      width: Dimensions.widthSize * 2.4,
                    ),
                  ),
                ),
              ),
              Padding(
                padding: EdgeInsets.only(left: Dimensions.paddingSize * 0.5),
                child: FittedBox(
                  fit: BoxFit.scaleDown,
                  child: TitleHeading3Widget(
                    text: title,
                    color: textColor ?? CustomColor.primaryLightTextColor,
                    fontWeight: FontWeight.w500,
                    fontSize: Dimensions.headingTextSize3 * 0.9,
                  ),
                ),
              ),
            ],
          ),
          Padding(
            padding: EdgeInsets.only(
                top: Dimensions.paddingSize * 0.8,
                right: Dimensions.paddingSize * 0.05),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                if (balance != null)
                  FittedBox(
                    fit: BoxFit.scaleDown,
                    child: TitleHeading4Widget(
                      text: balance!,
                      fontWeight: FontWeight.w700,
                      fontSize: Dimensions.headingTextSize4 * 0.9,
                    ),
                  ),
                if (balanceDescription != null)
                  TitleHeading5Widget(text: balanceDescription!),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
