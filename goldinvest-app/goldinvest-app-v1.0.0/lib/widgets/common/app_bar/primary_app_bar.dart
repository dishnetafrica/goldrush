import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/utils/custom_color.dart';

import '../../../utils/dimensions.dart';
import '../text_labels/title_heading2_widget.dart';
import 'back_button.dart';

class PrimaryAppBar extends StatelessWidget implements PreferredSizeWidget {
  const PrimaryAppBar(this.title,
      {super.key,
      this.backgroundColor,
      this.elevation = 0,
      this.autoLeading = false,
      this.showBackButton = true,
      this.centerTitle = true,
      this.action,
      this.leading,
      this.bottom,
      this.toolbarHeight,
      this.appbarSize,
      this.titleColor,
      this.onTap});

  final String title;
  final Color? backgroundColor;
  final double elevation;
  final List<Widget>? action;
  final Widget? leading;
  final bool autoLeading;
  final bool showBackButton;
  final bool centerTitle;
  final PreferredSizeWidget? bottom;
  final double? toolbarHeight;
  final double? appbarSize;
  final Color? titleColor;
  final Function? onTap;

  @override
  Widget build(BuildContext context) {
    return AppBar(
      centerTitle: centerTitle,
      title: TitleHeading2Widget(
        text: title,
        fontWeight: FontWeight.w800,
        color: CustomColor.blackTextColor,
      ),
      actions: action,
      leading: showBackButton
          ? leading ??
              BackButtonWidget(
                onTap: () {
                  Get.close(0);
                },
              )
          : null,
      bottom: bottom,
      elevation: elevation,
      toolbarHeight: toolbarHeight,
      scrolledUnderElevation: 0,
      backgroundColor:
          backgroundColor ?? CustomColor.blackColor.withValues(alpha: 0.05),
      automaticallyImplyLeading: autoLeading,
    );
  }

  @override
  // Size get preferredSize => Size.fromHeight(appBar.preferredSize.height);
  Size get preferredSize =>
      Size.fromHeight(appbarSize ?? Dimensions.appBarHeight);
}
