import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/model/common/common_success_model.dart';

import '../../../backend/services/auth/auth_services.dart';

class ResetPasswordController extends GetxController {
  final newPasswordController = TextEditingController();
  final confirmPasswordController = TextEditingController();

  RxBool isRemember = false.obs;
  // Routing
  dynamic get onResetPassword => resetPasswordProcess();

  String get onPrivacyPolicy => '';
  RxBool isFormValid = false.obs;

  @override
  void onInit() {
    newPasswordController.addListener(_updateFormValidity);
    confirmPasswordController.addListener(_updateFormValidity);

    super.onInit();
  }

  void _updateFormValidity() {
    isFormValid.value = newPasswordController.text.isNotEmpty &&
        confirmPasswordController.text.isNotEmpty;
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  Future<Future<CommonSuccessModel?>> resetPasswordProcess() async {
    return AuthServices.resetPasswordProcess(
        isLoading: _isLoading,
        password: newPasswordController.text,
        confirmPassword: confirmPasswordController.text);
  }
}
