import 'package:get/get.dart';
import 'package:goldinvest/controller/basic_settings_controller/basic_settings_controller.dart';

import '../controller/splash/splash_controller.dart';

class SplashBinding extends Bindings {
  @override
  void dependencies() {
    BasicServices.getBasicSettingsInfo();
    Get.put(SplashController());
  }
}
