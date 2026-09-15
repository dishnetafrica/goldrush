import 'package:get/get.dart';
import 'package:goldinvest/controller/auth/sign_in/otp_verification_controller.dart';

class OtpVerificationBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut(() => OtpVerificationController());
  }
}
