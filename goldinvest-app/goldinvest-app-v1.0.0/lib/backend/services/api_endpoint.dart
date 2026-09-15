import 'package:dynamic_languages/dynamic_languages.dart';

class ApiConfig {
  static const String mainDomain = "PUT-YOUR-DOMAIN-HERE";

  static const String baseUrl = "$mainDomain/api/v1";
  static const String languageUrl = "$baseUrl/settings/languages";
}

enum ApiEndpoint {
  // Settings
  basicSettings('/settings/basic-settings'),

  // Auth
  login('/login'),
  forgotPassword('/password/forgot/find/user'),
  forgotPasswordVerifyCode('/password/forgot/verify/code'),
  resendForgotOtpCode('/password/forgot/resend/code'),
  resetPassword('/password/forgot/reset'),

  register('/register'),
  emailOtpVerify('/authorize/mail/verify/code'),
  resendEmailOtp('/authorize/mail/resend/code'),

  logOut('/user/logout'),

  // kyc
  kycInfo('/authorize/kyc/input-fields'),
  kycSubmit('/authorize/kyc/submit'),

  // two fa

  twoFaStatus('/authorize/google/2fa/status'),
  twoFaStatusVerify('/authorize/google/2fa/status-update'),
  twoFaVerify('/authorize/google/2fa/verify'),

  // Profile
  profileInfo('/user/profile/info'),
  profileUpdate('/user/profile/info/update'),
  profilePasswordUpdate('/user/profile/password/update'),
  changePassword('/user/profile/password/update'),
  profileDelete('/user/profile/delete-account'),

  // Add Money
  addMoneySubmitAutomatic('/user/add-money/automatic/submit'),
  addMoneyManualGateways('/user/add-money/payment-gateways'),

  // dashboard

  dashboard('/user/dashboard'),
  myStatus('/user/status/info'),
  notification('/user/notifications'),

  // delivery

  states('/user/profile/states'),
  cities('/user/profile/cities'),

  // Investment

  goldStore('/user/order/getGolds'),
  goldCheckOut('/user/order/checkout'),
  goldSubmit('/user/order/submit'),
  myInvestment('/user/invest-plan/my-investments'),
  goldInvestPlan('/user/invest-plan/getPlans'),
  goldInvestPlanPurchase('/user/invest-plan/purchase'),

  // Transactions

  moneyOutSubmit('/user/withdraw/submit'),
  sendMoney('/user/money-transfer/submit'),
  sendMoneyWallet('/user/money-transfer/wallets'),
  moneyOut('/user/withdraw/wallet-gateways'),
  moneyOutInputField('/user/withdraw/gateway/input-fields'),
  addMoneyAdditionalField('/user/add-money/manual/input-fields'),
  addMoneySubmit('/user/add-money/manual/submit'),
  languageURL('/settings/languages'),

  // logs
  transactions('/user/transaction/log'),
  tatum('/user/add-money/payment/crypto/confirm'),
  orders('/user/order/log'),
  profit('/user/profit/log');

  final String path;
  const ApiEndpoint(this.path);

  String url({Map<String, String>? params}) {
    var fullUrl = "${ApiConfig.baseUrl}$path";
    if (params != null && params.isNotEmpty) {
      fullUrl +=
          '?${params.entries.map((e) => '${e.key}=${e.value}').join('&')}&?lang=${DynamicLanguage.selectedLanguage.value}';
    } else {
      fullUrl += '?lang=${DynamicLanguage.selectedLanguage.value}';
    }
    return fullUrl;
  }

  String withParams(Map<String, String> params) => url(params: params);
}
