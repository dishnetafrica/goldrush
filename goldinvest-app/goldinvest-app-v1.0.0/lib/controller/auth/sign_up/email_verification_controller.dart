import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/model/common/common_success_model.dart';

import '../../../backend/services/auth/auth_services.dart';

class EmailVerificationController extends GetxController {
  final pinCodeController = TextEditingController();

  // Routing
  dynamic get onSubmit => emailVerifyProcess();
  dynamic get onResendOtp => onResendOtpProcess();

  RxBool isFormValid = false.obs;

  @override
  void onInit() {
    pinCodeController.addListener(_updateFormValidity);
    timerInit();

    super.onInit();
  }

  void timerInit() {
    timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (secondsRemaining.value != 0) {
        secondsRemaining.value--;
      } else {
        enableResend.value = true;
      }
    });
  }

  RxInt secondsRemaining = 59.obs;
  RxBool enableResend = false.obs;
  Timer? timer;

  void _updateFormValidity() {
    isFormValid.value = pinCodeController.text.length == 6;
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  Future<Future<CommonSuccessModel?>> emailVerifyProcess() async {
    return AuthServices.emailVerifyProcess(
        code: pinCodeController.text, isLoading: _isLoading);
  }

  final _isResendLoading = false.obs;
  bool get isResendLoading => _isResendLoading.value;

  Future<CommonSuccessModel?> onResendOtpProcess() {
    return AuthServices.resendEmailOtpCode(isResendLoading: _isResendLoading);
  }
}
