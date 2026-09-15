import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../backend/model/common/common_success_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';

class ChangePasswordController extends GetxController {
  final changePasswordController = TextEditingController();
  final passwordController = TextEditingController();
  final passwordConfirmationController = TextEditingController();

  @override
  void onClose() {
    changePasswordController.dispose();
    passwordController.dispose();
    passwordConfirmationController.dispose();
    super.onClose();
  }

  static late CommonSuccessModel _commonSuccessModel;
  CommonSuccessModel get commonSuccessModel => _commonSuccessModel;

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;
  Future<CommonSuccessModel?> onChangePassword() async {
    Map<String, dynamic> inputBody = {
      'current_password': changePasswordController.text,
      'password': passwordController.text,
      'password_confirmation': passwordConfirmationController.text,
    };
    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.changePassword,
      isLoading: _isLoading,
      method: HttpMethod.POST,
      body: inputBody,
      isBasic: false,
      onSuccess: (value) {
        _commonSuccessModel = value!;
      },
    );
  }
}
