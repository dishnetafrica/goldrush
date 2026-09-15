import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';
import 'package:get/get.dart';

import '../../controller/navigation/navigation_controller.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';

class BottomItemWidget extends StatelessWidget {
  BottomItemWidget({super.key, this.icon, required this.label, this.index});
  final String? icon;
  final String label;
  final int? index;
  final controller = Get.put(NavigationController());

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        controller.selectedIndex.value = index!;
      },
      child: Obx(() => SizedBox(
            width: Dimensions.widthSize * 5.8,
            child: Column(
              children: [
                SvgPicture.asset(
                  icon ?? "",
                  height: Dimensions.heightSize * 2,
                  width: Dimensions.widthSize * 2.4,
                  // ignore: deprecated_member_use
                  color: controller.selectedIndex.value == index
                      ? CustomColor.primaryLightColor
                      : CustomColor.primaryLightColor.withValues(alpha: 0.5),
                ),
                verticalSpace(Dimensions.heightSize * 0.2),
                Text(
                  DynamicLanguage.isLoading ? "" : DynamicLanguage.key(label),
                  style: TextStyle(
                    fontSize: Dimensions.headingTextSize5 * 0.9,
                    fontWeight: FontWeight.w600,
                    color: controller.selectedIndex.value == index
                        ? CustomColor.primaryLightColor
                        : CustomColor.primaryLightColor.withValues(alpha: 0.5),
                  ),
                ),
              ],
            ),
          )),
    );
  }
}
