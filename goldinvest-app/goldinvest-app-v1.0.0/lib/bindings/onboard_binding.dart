import 'package:get/get.dart';

import '../controller/on_board_controller/onboard_controller.dart';

class OnboardBinding extends Bindings {
  @override
  void dependencies() {
    Get.put(OnboardController());
  }
}
