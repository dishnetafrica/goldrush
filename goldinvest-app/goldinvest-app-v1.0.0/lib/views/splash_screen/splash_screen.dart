import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/utils/custom_loading_api.dart';
import 'package:goldinvest/controller/splash/splash_controller.dart';
import 'package:goldinvest/widgets/splash_widgets/app_version.dart';
import '../../controller/basic_settings_controller/basic_settings_controller.dart';
import '../../utils/responsive_layout.dart';
import '../../widgets/splash_widgets/splash_image.dart';

class SplashScreen extends GetView<SplashController> {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        body: _bodyWidget(context),
      ),
    );
  }

  Obx _bodyWidget(BuildContext context) {
    return Obx(
      () => BasicServices.isLoading
          ? const CustomLoadingAPI()
          : const SafeArea(
              child: Stack(
                alignment: Alignment.center,
                children: [
                  SplashImage(),
                  Positioned(
                    bottom: 10,
                    child: AppVersion(),
                  ),
                ],
              ),
            ),
    );
  }
}
