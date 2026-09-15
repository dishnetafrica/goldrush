import 'package:get/get.dart';
import 'package:goldinvest/controller/auth/sign_up/email_verification_controller.dart';

class EmailVerificationBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut(() => EmailVerificationController());
  }
}
