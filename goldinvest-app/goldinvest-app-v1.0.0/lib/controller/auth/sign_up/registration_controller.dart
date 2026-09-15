import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/model/auth/sign_up_model.dart';

import 'package:goldinvest/routes/routes.dart';

import '../../../backend/services/auth/auth_services.dart';

class RegistrationController extends GetxController {
  final emailAddressController = TextEditingController();
  final passwordController = TextEditingController();
  final firstNameController = TextEditingController();
  final lastNameController = TextEditingController();
  final referralIdController = TextEditingController();
  RxBool agree = false.obs;
  // Routing
  dynamic get onRegistration => registrationProcess();
  String get onLogIn => Routes.signInScreen;
  String get onPrivacyPolicy => '';
  RxBool isFormValid = false.obs;

  @override
  void onInit() {
    emailAddressController.addListener(_updateFormValidity);
    passwordController.addListener(_updateFormValidity);
    firstNameController.addListener(_updateFormValidity);
    lastNameController.addListener(_updateFormValidity);
    super.onInit();
  }

  void _updateFormValidity() {
    isFormValid.value = emailAddressController.text.isNotEmpty &&
        passwordController.text.isNotEmpty &&
        firstNameController.text.isNotEmpty &&
        lastNameController.text.isNotEmpty;
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  Future<Future<SignUpModel?>> registrationProcess() async {
    return AuthServices.registrationProcess(
      refer: referralIdController.text,
      firstName: firstNameController.text,
      lastName: lastNameController.text,
      email: emailAddressController.text,
      password: passwordController.text,
      isLoading: _isLoading,
      isAgree: agree,
    );
  }
}
