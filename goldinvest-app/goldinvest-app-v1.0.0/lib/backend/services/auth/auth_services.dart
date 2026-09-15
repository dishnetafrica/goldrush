import 'package:get/get.dart';
import 'package:goldinvest/extensions/extensions.dart';

import '../../../../routes/routes.dart';
import '../../../languages/strings.dart';
import '../../../views/congratulation/congratulation_screen.dart';
import '../../local_storage/local_storage.dart';
import '../../model/auth/forgot_password_model.dart';
import '../../model/auth/resend_succes_model.dart';
import '../../model/auth/sign_in_model.dart';
import '../../model/auth/sign_up_model.dart';
import '../../model/common/common_success_model.dart';
import '../../utils/request_process.dart';
import '../api_endpoint.dart';

class AuthServices {
  static late SignInModel _logInModel;
  SignInModel get logInModel => _logInModel;

  static late CommonSuccessModel _commonSuccessModel;
  CommonSuccessModel get commonSuccessModel => _commonSuccessModel;

  static late ResetPasswordModel _reseetPasswordSuccessModel;
  ResetPasswordModel get reseetPasswordSuccessModel =>
      _reseetPasswordSuccessModel;

  static late ForgotPasswordModel _forgotPasswordAndVerifyModel;
  ForgotPasswordModel get forgotPasswordAndVerifyModel =>
      _forgotPasswordAndVerifyModel;

  static late SignUpModel _registerModel;
  SignUpModel get registerModel => _registerModel;

  static Future<SignInModel?> logInService({
    required String credentials,
    required String password,
    required RxBool isLoading,
  }) async {
    Map<String, dynamic> inputBody = {
      'credentials': credentials,
      'password': password,
    };
    return RequestProcess().request<SignInModel>(
      fromJson: SignInModel.fromJson,
      apiEndpoint: ApiEndpoint.login,
      isLoading: isLoading,
      method: HttpMethod.POST,
      body: inputBody,
      isBasic: true,

      showErrorMessage: true,
      onSuccess: (value) {
        _logInModel = value!;
        var data = _logInModel.data;
        LocalStorage.save(
          token: data.token,
          temporaryToken: data.authorization.token,
          isLoggedIn: true,
          isEmailVerified: data.userInfo.emailVerified == 1,
        );
        if (data.userInfo.twoFactorStatus == 1 &&
            data.userInfo.twoFactorVerified == 0) {
          Get.toNamed(Routes.twoFaSecurityVerify);
        } else if (data.userInfo.emailVerified == 1) {
          Get.offAllNamed(Routes.home);
        } else if (data.userInfo.emailVerified == 0) {
          Get.toNamed(Routes.emailVerificationScreen);
        }
      },
    );
  }

  static Future<ForgotPasswordModel?> forgotPasswordProcess({
    required String credentials,
    required RxBool isLoading,
  }) async {
    Map<String, dynamic> inputBody = {'credentials': credentials};
    return RequestProcess().request<ForgotPasswordModel>(
      fromJson: ForgotPasswordModel.fromJson,
      apiEndpoint: ApiEndpoint.forgotPassword,
      isLoading: isLoading,
      method: HttpMethod.POST,
      showErrorMessage: true,
      isBasic: true,
      body: inputBody,
      onSuccess: (value) {
        _forgotPasswordAndVerifyModel = value!;
        var data = _forgotPasswordAndVerifyModel.data;
        LocalStorage.save(temporaryToken: data.token);
        Routes.forgetOtpVerificationScreen.toNamed;
      },
    );
  }

  static Future<ResetPasswordModel?> resendForgotOtpCode({
    required RxBool isResendLoading,
  }) async {
    return RequestProcess().request<ResetPasswordModel>(
      fromJson: ResetPasswordModel.fromJson,
      apiEndpoint: ApiEndpoint.resendForgotOtpCode,
      queryParams: {'token': LocalStorage.temporaryToken},
      isLoading: isResendLoading,
      onSuccess: (value) {
        _reseetPasswordSuccessModel = value!;
      },
    );
  }

  static Future<ForgotPasswordModel?> otpVerifyProcess({
    required String code,
    required RxBool isLoading,
  }) async {
    Map<String, dynamic> inputBody = {
      'token': LocalStorage.temporaryToken,
      'code': code,
    };

    return RequestProcess().request<ForgotPasswordModel>(
      fromJson: ForgotPasswordModel.fromJson,
      apiEndpoint: ApiEndpoint.forgotPasswordVerifyCode,
      isLoading: isLoading,
      method: HttpMethod.POST,
      body: inputBody,
      showErrorMessage: true,
      onSuccess: (value) {
        _forgotPasswordAndVerifyModel = value!;
        Routes.resetPasswordScreen.toNamed;
      },
    );
  }

  static Future<CommonSuccessModel?> resetPasswordProcess({
    required RxBool isLoading,
    required String password,
    required String confirmPassword,
  }) async {
    Map<String, dynamic> inputBody = {
      'token': LocalStorage.temporaryToken,
      'password': password,
      'password_confirmation': confirmPassword,
    };

    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.resetPassword,
      isLoading: isLoading,
      method: HttpMethod.POST,
      body: inputBody,
      showErrorMessage: true,
      showSuccessMessage: true,
      onSuccess: (value) {
        Get.to(
          () => const CongratulationScreen(
            subTitleString: Strings.congratulationDetails,
            route: Routes.signInScreen,
          ),
        );
      },
    );
  }

  // Register
  static Future<SignUpModel?> registrationProcess({
    required String firstName,
    required String lastName,
    required String email,
    required String password,
    required String refer,
    required RxBool isLoading,
    required RxBool isAgree,
  }) async {
    Map<String, dynamic> inputBody = {
      'firstname': firstName,
      'lastname': lastName,
      'email': email,
      'password': password,
      'agree': 'on',
      'refer': refer,
    };
    return RequestProcess().request<SignUpModel>(
      fromJson: SignUpModel.fromJson,
      apiEndpoint: ApiEndpoint.register,
      isLoading: isLoading,
      method: HttpMethod.POST,
      body: inputBody,
      showErrorMessage: true,
      isBasic: true,
      onSuccess: (value) {
        _registerModel = value!;
        var data = _registerModel.data;
        LocalStorage.save(
          token: data.token,
          temporaryToken: data.authorization.token,
          isLoggedIn: true,
          isKycVerified: data.userInfo.kycVerified == 1,
          isEmailVerified: data.userInfo.emailVerified == 1,
        );
        if (data.userInfo.emailVerified == 1) {
          if (data.userInfo.kycVerified == 1) {
            Routes.home.offAllNamed;
          } else {
            Routes.kycVerification.toNamed;
          }
        } else {
          Routes.emailVerificationScreen.toNamed;
        }
      },
    );
  }

  static Future<CommonSuccessModel?> emailVerifyProcess({
    required String code,
    required RxBool isLoading,
  }) async {
    Map<String, dynamic> inputBody = {
      'token': LocalStorage.temporaryToken,
      'code': code,
    };

    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.emailOtpVerify,
      isLoading: isLoading,
      method: HttpMethod.POST,
      showErrorMessage: true,
      body: inputBody,
      onSuccess: (value) {
        _commonSuccessModel = value!;
        if (LocalStorage.isKycVerified) {
          Get.to(
            () => const CongratulationScreen(
              subTitleString: Strings.yourAccountHas,
              route: Routes.signInScreen,
            ),
          );
        } else {
          Routes.kycVerification.toNamed;
        }
      },
    );
  }

  static Future<CommonSuccessModel?> resendEmailOtpCode({
    required RxBool isResendLoading,
  }) async {
    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      method: HttpMethod.POST,
      apiEndpoint: ApiEndpoint.resendEmailOtp,
      queryParams: {'token': LocalStorage.temporaryToken},
      isLoading: isResendLoading,
      onSuccess: (value) {
        _commonSuccessModel = value!;
      },
    );
  }

  static Future<CommonSuccessModel?> logOutService({
    required RxBool isLoading,
  }) async {
    Map<String, dynamic> inputBody = {};
    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.logOut,
      isLoading: isLoading,
      method: HttpMethod.POST,
      body: inputBody,
      onSuccess: (value) {
        _commonSuccessModel = value!;
        LocalStorage.clear();
        Get.offAllNamed(Routes.signInScreen);
      },
    );
  }
}
