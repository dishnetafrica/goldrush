import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:goldinvest/views/drawer/drawer_screen.dart';
import 'package:goldinvest/views/investment/investment_screen.dart';

import '../../controller/navigation/navigation_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../widgets/bottom_Items/bottom_items_widget.dart';
import '../../widgets/common/others/custom_image_widget.dart';

class HomeScreen extends StatelessWidget {
  HomeScreen({super.key});
  final GlobalKey<ScaffoldState> _key = GlobalKey();
  final NavigationController navController = Get.put(NavigationController());

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => ResponsiveLayout(
          mobileScaffold: SafeArea(
        child: Scaffold(
          extendBody: true,
          key: _key,
          drawer: DrawerScreen(),
          backgroundColor: CustomColor.backgroundColor.withValues(alpha: 0.97),
          body: navController.bodyPages[navController.selectedIndex.value],
          bottomNavigationBar: _bottomNavBarWidget(),
          floatingActionButton: _middleButton(context),
          floatingActionButtonLocation:
              FloatingActionButtonLocation.centerDocked,
        ),
      )),
    );
  }

  Container? _middleButton(BuildContext context) {
    return MediaQuery.of(context).viewInsets.bottom == 0
        ? Container(
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                  color: CustomColor.blackColor.withValues(alpha: 0.15),
                  spreadRadius: 15,
                  blurRadius: 35,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: GestureDetector(
              onTap: () => Get.to(const InvestmentScreen(
                selectTab: 0,
              )),
              child: CircleAvatar(
                radius: Dimensions.radius * 2.6,
                backgroundColor: CustomColor.primaryLightColor,
                child: Padding(
                  padding: EdgeInsets.all(Dimensions.paddingSize * 0.5),
                  child: CustomImageWidget(
                    path: Assets.icon.statusUp,
                    height: Dimensions.heightSize * 2,
                    width: Dimensions.widthSize * 2.4,
                  ),
                ),
              ),
            ),
          )
        : null;
  }

  BottomAppBar _bottomNavBarWidget() {
    return BottomAppBar(
        elevation: 8,
        shadowColor: CustomColor.blackColor,
        color: CustomColor.whiteColor,
        padding: EdgeInsets.zero,
        clipBehavior: Clip.antiAlias,
        height: Dimensions.heightSize * 6,
        shape: const CircularNotchedRectangle(),
        notchMargin: 12,
        child: Row(mainAxisAlignment: mainSpaceBet, children: [
          Expanded(
            child: Padding(
              padding:
                  EdgeInsets.only(left: 0.5, top: Dimensions.paddingSize * 0.2),
              child: BottomItemWidget(
                  icon: Assets.icon.home2, label: Strings.home, index: 0),
            ),
          ),
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.2),
              child: BottomItemWidget(
                  icon: Assets.icon.notification,
                  label: Strings.notification,
                  index: 1),
            ),
          ),
          horizontalSpace(Dimensions.widthSize * 8),
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.2),
              child: BottomItemWidget(
                  icon: Assets.icon.chartSquare,
                  label: Strings.myStatus,
                  index: 2),
            ),
          ),
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(
                  left: Dimensions.paddingSize * 0.5,
                  top: Dimensions.paddingSize * 0.2),
              child: BottomItemWidget(
                  icon: Assets.icon.refresh, label: Strings.history, index: 3),
            ),
          ),
        ]));
  }
}
