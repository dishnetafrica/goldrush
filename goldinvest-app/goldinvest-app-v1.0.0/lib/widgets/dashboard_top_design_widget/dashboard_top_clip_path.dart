import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading3_widget.dart';

import '../../backend/model/dashboard/dashboard_model.dart';
import '../../controller/dashboard/dashboard_coontroller.dart';
import '../../controller/navigation/navigation_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading1_widget.dart';
import '../common/text_labels/title_heading5_widget.dart';

class DashboardTopClipPath extends StatelessWidget {
  DashboardTopClipPath({super.key});
  final controller = Get.put(DashboardController());
  final navController = Get.put(NavigationController());

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<DashboardModel?>(
        stream: controller.getDashboardDataStream(),
        builder: (context, snapshot) {
          return Column(
            children: [
              verticalSpace(Dimensions.widthSize * 1.8),
              Container(
                decoration: BoxDecoration(
                  shape: BoxShape.rectangle,
                  borderRadius: BorderRadius.circular(Dimensions.radius * 2),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.05),
                      spreadRadius: 3,
                      blurRadius: 10,
                      offset: const Offset(0, 0),
                    ),
                  ],
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(Dimensions.radius * 2),
                  child: Stack(
                    clipBehavior: Clip.hardEdge,
                    children: [
                      //fourth shade
                      ClipPath(
                        clipper: Custom4Clipper(),
                        child: Container(
                          decoration: BoxDecoration(
                            color: CustomColor.whiteColor,
                            borderRadius:
                                BorderRadius.circular(Dimensions.radius * 2),
                          ),
                          height: Dimensions.heightSize * 16,
                          width: Dimensions.widthSize * 32,
                        ),
                      ),
                      //third shade
                      ClipPath(
                        clipper: Custom1Clipper(),
                        child: Container(
                          decoration: BoxDecoration(
                            color:
                                CustomColor.primaryLightColor.withValues(alpha: 0.05),
                            borderRadius:
                                BorderRadius.circular(Dimensions.radius * 2),
                          ),
                          height: Dimensions.heightSize * 16,
                          width: Dimensions.widthSize * 32,
                        ),
                      ),
                      //second shade
                      ClipPath(
                        clipper: Custom2Clipper(),
                        child: Container(
                          decoration: BoxDecoration(
                            color:
                                CustomColor.primaryLightColor.withValues(alpha: 0.1),
                            borderRadius:
                                BorderRadius.circular(Dimensions.radius * 2),
                          ),
                          height: Dimensions.heightSize * 16,
                          width: Dimensions.widthSize * 32,
                        ),
                      ),
                      //first shade
                      ClipPath(
                        clipper: Custom3Clipper(),
                        child: Container(
                          decoration: BoxDecoration(
                            color:
                                CustomColor.primaryLightColor.withValues(alpha: 0.1),
                            borderRadius:
                                BorderRadius.circular(Dimensions.radius * 2),
                          ),
                          height: Dimensions.heightSize * 16,
                          width: Dimensions.widthSize * 32,
                          // color: CustomColor.primaryLightColor.withValues(alpha: 0.1),
                        ),
                      ),
                      _balanceCardInfoWidget(context),
                    ],
                  ),
                ),
              ),
            ],
          );
        });
  }

  Obx _balanceCardInfoWidget(context) {
    return Obx(
      () => Padding(
        padding: EdgeInsets.symmetric(
          horizontal: Dimensions.paddingSize,
          vertical: Dimensions.paddingSize,
        ),
        child: Row(
          mainAxisAlignment: mainSpaceBet,
          crossAxisAlignment: crossCenter,
          children: [
            Column(
              mainAxisAlignment: mainCenter,
              crossAxisAlignment: crossStart,
              children: [
                Padding(
                    padding:
                        EdgeInsets.symmetric(vertical: Dimensions.heightSize),
                    child: Column(
                      crossAxisAlignment: crossStart,
                      children: [
                        TitleHeading1Widget(
                          text:
                              "${controller.currentBalance.value.toStringAsFixed(2)}"
                              " "
                              "${controller.currencyCode.value}",
                          fontWeight: FontWeight.w700,
                          color: CustomColor.primaryLightTextColor,
                        ),
                        const TitleHeading3Widget(
                          text: Strings.currentBalance,
                          color: CustomColor.liteBlack2,
                          fontWeight: FontWeight.w500,
                        )
                      ],
                    )),
                TitleHeading3Widget(
                    text:
                        "${controller.investmentAmount.value.toStringAsFixed(2)}"
                        " "
                        "${controller.currencyCode.value}",
                    fontWeight: FontWeight.w700,
                    color: CustomColor.liteBlack1),
                TitleHeading5Widget(
                    text: Strings.investment,
                    fontWeight: FontWeight.w500,
                    fontSize: Dimensions.headingTextSize6,
                    color: CustomColor.primaryLightTextColor)
              ],
            ),
            Column(
                crossAxisAlignment: crossCenter,
                mainAxisAlignment: mainCenter,
                children: [
                  Padding(
                    padding: EdgeInsets.symmetric(
                        vertical: Dimensions.paddingSize * 1.5),
                    child: GestureDetector(
                      onTap: () {
                        navController.changePage(3);
                      },
                      child: Row(
                        children: [
                          CustomImageWidget(path: Assets.icon.maximize),
                          Padding(
                            padding: EdgeInsets.only(
                                left: Dimensions.paddingSize * 0.3),
                            child: const TitleHeading5Widget(
                              text: Strings.viewAll,
                              color: CustomColor.primaryLightColor,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  Column(
                    crossAxisAlignment: crossEnd,
                    children: [
                      TitleHeading3Widget(
                        text:
                            "${controller.totalProfit.value.toStringAsFixed(2)}"
                            " "
                            "${controller.currencyCode.value}",
                        color: CustomColor.liteBlack1,
                      ),
                      TitleHeading5Widget(
                        text: Strings.totalProfit,
                        fontSize: Dimensions.headingTextSize6,
                        color: CustomColor.liteBlack1,
                      )
                    ],
                  )
                ]),
          ],
        ),
      ),
    );
  }
}

class Custom1Clipper extends CustomClipper<Path> {
  @override
  Path getClip(Size size) {
    Path path = Path();

    path.lineTo(0, size.height);
    path.lineTo(size.width / 1.6, size.height);
    path.lineTo(size.width / 1.1, 0);
    return path;
  }

  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}

class Custom2Clipper extends CustomClipper<Path> {
  @override
  Path getClip(Size size) {
    Path path = Path();

    path.lineTo(0, size.height);
    path.lineTo(size.width / 2.2, size.height);
    path.lineTo(size.width / 1.35, 0);
    return path;
  }

  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}

class Custom3Clipper extends CustomClipper<Path> {
  @override
  Path getClip(Size size) {
    Path path = Path();

    path.lineTo(0, size.height);
    path.lineTo(size.width / 2.6, size.height);
    path.lineTo(size.width / 1.48, 0);
    return path;
  }

  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}

class Custom4Clipper extends CustomClipper<Path> {
  @override
  Path getClip(Size size) {
    Path path = Path();

    path.lineTo(0, size.height);
    path.lineTo(size.width, size.height);
    path.lineTo(size.width, 0);
    return path;
  }

  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}
