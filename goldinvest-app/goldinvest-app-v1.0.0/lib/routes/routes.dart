import 'package:get/get.dart';

import '../bindings/email_verification_binding.dart';
import '../bindings/gold_store_binding.dart';
import '../bindings/notification_binding.dart';
import '../bindings/onboard_binding.dart';
import '../bindings/otp_verification_binding.dart';
import '../bindings/profile_binding.dart';
import '../bindings/reset_password_binding.dart';
import '../bindings/sign_in_binding.dart';
import '../bindings/sign_up_binding.dart';
import '../bindings/splash_screen_binding.dart';
import '../views/add_money/add_money_congratulation_screen.dart';

import '../views/add_money/add_money_manual_payment_screen.dart';
import '../views/add_money/add_money_screen.dart';
import '../views/auth/sign_in/otp_verification_screen.dart';
import '../views/auth/sign_in/reset_password_screen.dart';
import '../views/auth/sign_in/sign_in_screen.dart';
import '../views/auth/sign_up/email_verification_screen.dart';
import '../views/auth/sign_up/sign_up_mobile_screen.dart';
import '../views/change_password/change_password_screen.dart';
import '../views/checkout/checkout_screen.dart';
import '../views/dashboard/dashboard_screen.dart';
import '../views/delivery_address/delivery_address_screen.dart';
import '../views/gold_invest/gold_invest_congratulation_screen.dart';
import '../views/gold_store/gold_store_screen.dart';
import '../views/history/history_screen.dart';
import '../views/home/home_screen.dart';
import '../views/kyc_verification/kyc_verification_screen.dart';
import '../views/money_out/money_out_congratulation_screen.dart';
import '../views/money_out/money_out_manual_payment_screen.dart';
import '../views/money_out/money_out_screen.dart';
import '../views/notifications/notification_screen.dart';
import '../views/onboard/onboard_screen.dart';
import '../views/order_logs/order_logs_screen.dart';
import '../views/profile/profile_screen.dart';
import '../views/referral_users/referral_users_screen.dart';
import '../views/send_money/send_money_congratulation_screen.dart';
import '../views/send_money/send_money_screen.dart';
import '../views/settings/settings.dart';
import '../views/splash_screen/splash_screen.dart';
import '../views/transation_logs/transaction_screen.dart';
import '../views/two_fa_security/two_fa_security_screen.dart';
import '../views/two_fa_security/two_fa_securtiy_verification_screen.dart';

part '../routes/route_pages.dart';

class Routes {
  // Page List
  static var list = RoutePageList.list;

  // Route Names
  static const String home = '/home';
  static const String dashboardScreen = '/dashboard';
  static const String splashScreen = '/splashScreen';
  static const String onboardScreen = '/onboard_screen';
  static const String signInScreen = '/signIn_screen';
  static const String forgetOtpVerificationScreen =
      '/ForgetOtpVerificationScreen';
  static const String otpVerificationScreen = '/otp_verification_screen';
  static const String resetPasswordScreen = '/reset_password_screen';
  static const String signUpScreen = '/sign_up_screen';
  static const String emailVerificationScreen = '/email_verification_screen';
  static const String confirmationScreen = '/confirmation_screen';
  static const String congratulationScreen = '/congratulation_screen';

  static const String notificationScreen = '/notification';
  static const String referralScreen = '/referral_screen';
  static const String historyScreen = '/history_screen';
  static const String profile = "/profile_screen";
  static const String kycVerification = "/kyc_verification_screen";
  static const String twoFaSecurity = "/two_fa_security";
  static const String changePassword = "/change_password";
  static const String addMoneyScreen = "/add_money_screen";
  static const String addMoneyCongratulation = "/add_money_congratulation";
  static const String moneyOutScreen = "/money_out_screen";
  static const String moneyOutCongratulation = "/money_out_congratulation";
  static const String sendMoneyScreen = "/send_money_screen";
  static const String sendMoneyCongratulationScreen =
      "/send_money_congratulation";
  static const String goldinvest = "/gold_invest_screen";
  static const String goldStore = "/gold_store_screen";
  static const String myInvest = "/my_invest_screen";
  static const String investment = "/investment";
  static const String goldInvestCongratulation = "/gold_invest_congratulation";
  static const String deliveryAddress = "/delivery_address";
  static const String checkout = "/check_out_screen";
  static const String goldStoreCongratulation =
      "/gold_store_congratulation_screen";
  static const String moneyOutManualPaymentScreen =
      '/moneyOutManualPaymentScreen';
  static const String addMoneyManualPaymentScreen =
      '/addMoneyManualPaymentScreen';
  static const String twoFaSecurityVerify = '/twoFaSecurityVerifyScreen';
  static const String settings = '/settingsScreen';
  static const String transactionLogs = '/transactionLogs';
  static const String orderLogs = '/orderLogs';
}
