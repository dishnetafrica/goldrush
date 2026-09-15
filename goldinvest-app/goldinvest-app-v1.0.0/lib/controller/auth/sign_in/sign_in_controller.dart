import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/model/auth/forgot_password_model.dart';
import 'package:goldinvest/backend/model/auth/sign_in_model.dart';
import 'package:goldinvest/backend/model/common/common_success_model.dart';

import '../../../backend/services/auth/auth_services.dart';

class SignInController extends GetxController {
  final emailAddressController = TextEditingController();
  final forgotPasswordEmailAddressController = TextEditingController();
  final passwordController = TextEditingController();
  RxBool isRemember = false.obs;
  // Routing
  dynamic get onSignIn => signInProcess();
  dynamic get onForgetPassword => forgetPassword();
  dynamic get onSignOut => logOutProcess();
  String get onPrivacyPolicy => '';
  RxBool isFormValid = false.obs;

  @override
  void onInit() {
    // emailAddressController.text = 'dehan@appdevs.team';
    // passwordController.text = 'Appdevs@123';
    emailAddressController.addListener(_updateFormValidity);
    passwordController.addListener(_updateFormValidity);

    super.onInit();
  }

  void _updateFormValidity() {
    isFormValid.value =
        emailAddressController.text.isNotEmpty &&
        passwordController.text.isNotEmpty;
  }

  final _isLoadingSignIn = false.obs;
  bool get isLoadingSignIn => _isLoadingSignIn.value;

  Future<Future<SignInModel?>> signInProcess() async {
    return AuthServices.logInService(
      credentials: emailAddressController.text,
      password: passwordController.text,
      isLoading: _isLoadingSignIn,
    );
  }

  final _isLoadingLogOut = false.obs;
  bool get isLoadingLogout => _isLoadingLogOut.value;

  Future<Future<CommonSuccessModel?>> logOutProcess() async {
    return AuthServices.logOutService(isLoading: _isLoadingLogOut);
  }

  final _isLoadingForgetPassword = false.obs;
  bool get isLoadingForgetPassword => _isLoadingForgetPassword.value;

  Future<Future<ForgotPasswordModel?>> forgetPassword() async {
    return AuthServices.forgotPasswordProcess(
      credentials: forgotPasswordEmailAddressController.text,
      isLoading: _isLoadingForgetPassword,
    );
  }
}
