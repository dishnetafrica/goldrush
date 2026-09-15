import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/controller/kyc/kyc_controller.dart';
import 'package:goldinvest/utils/custom_color.dart';
import 'package:goldinvest/widgets/common/others/custom_image_widget.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading2_widget.dart';

import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/text_labels/title_heading4_widget.dart';

class KycHeadingWidget extends StatelessWidget {
  KycHeadingWidget({super.key});
  final controller = Get.put(KycInformationController());

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSize,
      ),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          Row(
            children: [
              const TitleHeading2Widget(
                text: Strings.kycInformation,
                fontWeight: FontWeight.bold,
              ),
              horizontalSpace(Dimensions.paddingSize * 0.3),
              Container(
                padding: EdgeInsets.all(Dimensions.paddingSize * 0.2),
                decoration: BoxDecoration(
                  color: () {
                    if (controller.status.value == 1) {
                      return CustomColor.liteGreenColor.withValues(alpha: 0.2);
                    } else if (controller.status.value == 2) {
                      return CustomColor.liteOrangeColor.withValues(alpha: 0.2);
                    } else {
                      return CustomColor.liteRedColor.withValues(alpha: 0.2);
                    }
                  }(),
                  borderRadius: BorderRadius.circular(Dimensions.radius * 0.5),
                ),
                child: Row(
                  children: [
                    () {
                      if (controller.status.value == 1) {
                        return CustomImageWidget(path: Assets.icon.checkCircle);
                      } else if (controller.status.value == 2) {
                        return CustomImageWidget(path: Assets.icon.tablerClock);
                      } else {
                        return CustomImageWidget(
                            path: Assets.icon.letsIconsCancel);
                      }
                    }(),
                    TitleHeading4Widget(
                      text: () {
                        if (controller.status.value == 1) {
                          return Strings.accepted;
                        } else if (controller.status.value == 2) {
                          return Strings.pending;
                        } else {
                          return Strings.unverified;
                        }
                      }(),
                      fontWeight: FontWeight.w400,
                      color: () {
                        if (controller.status.value == 1) {
                          return CustomColor.liteGreenColor;
                        } else if (controller.status.value == 2) {
                          return CustomColor.liteOrangeColor;
                        } else {
                          return CustomColor.liteRedColor;
                        }
                      }(),
                    )
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
