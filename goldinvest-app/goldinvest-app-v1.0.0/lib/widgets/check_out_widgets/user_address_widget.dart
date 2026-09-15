import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:intl/intl.dart';

import '../../controller/checkout/checkout_controller.dart';
import '../../controller/profile/profile_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading4_widget.dart';
import '../common/text_labels/title_heading5_widget.dart';
import '../time_widget/time_widget.dart';

class UserAddressWidget extends StatelessWidget {
  UserAddressWidget({super.key});
  final CheckoutController checkoutController = Get.put(CheckoutController());
  final ProfileController profileController = Get.put(ProfileController());

  @override
  Widget build(BuildContext context) {
    DateTime now = DateTime.now();
    final dateOnly = DateFormat('dd').format(now);
    final monthOnly = DateFormat('MMM').format(now);
    return Column(
      crossAxisAlignment: crossStart,
      children: [
        Container(
            decoration: BoxDecoration(
                color: CustomColor.blackColor.withValues(alpha: 0.05),
                borderRadius: BorderRadius.circular(Dimensions.radius)),
            height: Dimensions.heightSize * 6.67,
            child: Row(
              children: [
                Padding(
                  padding: EdgeInsets.only(left: Dimensions.paddingSize * 0.5),
                  child: Container(
                      decoration: BoxDecoration(
                          borderRadius:
                              BorderRadius.circular(Dimensions.radius),
                          color: CustomColor.whiteColor),
                      child: DateInfoWidget(
                        horizontalPadding: Dimensions.paddingSize * 0.4,
                        verticalPadding: Dimensions.paddingSize * 0.1,
                        dateText: dateOnly,
                        dateTextStyle: TextStyle(
                          fontSize: Dimensions.headingTextSize1,
                        ),
                        monthText: monthOnly,
                        monthTextStyle: TextStyle(
                            fontSize: Dimensions.headingTextSize2 * 0.5),
                        backgroundColor: CustomColor.whiteColor,
                      )),
                ),
                Padding(
                  padding: EdgeInsets.only(left: Dimensions.paddingSize * 0.5),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: crossStart,
                    children: [
                      Row(
                        children: [
                          CustomImageWidget(path: Assets.icon.location),
                          const TitleHeading4Widget(
                            text: Strings.homeAddress,
                            fontWeight: FontWeight.w400,
                            color: CustomColor.liteBlack2,
                          ),
                          horizontalSpace(Dimensions.paddingSize * 1.5),
                          InkWell(
                              onTap: () {
                                Get.toNamed(Routes.deliveryAddress);
                              },
                              child: const TitleHeading5Widget(
                                text: Strings.editAddress,
                                fontWeight: FontWeight.w500,
                                color: CustomColor.primaryLightColor,
                              )),
                        ],
                      ),
                      SizedBox(
                        width: MediaQuery.of(context).size.width * 0.65,
                        child: TitleHeading4Widget(
                          padding: EdgeInsets.only(
                              top: Dimensions.paddingSize * 0.2),
                          text:
                              "${profileController.addressController.text} ${profileController.zipCodeController.text} ${profileController.selectCity.value} ${profileController.selectState.value} ${profileController.selectCountry.value}",
                          textOverflow: TextOverflow.visible,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ))
      ],
    );
  }
}
