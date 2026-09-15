part of '../routes/routes.dart';

class RoutePageList {
  static var list = [
    GetPage(
      name: Routes.home,
      page: () => HomeScreen(),
      binding: ProfileBinding(),
    ),
    GetPage(
      name: Routes.dashboardScreen,
      page: () => const DashboardScreen(),
    ),
    GetPage(
      name: Routes.onboardScreen,
      page: () => const OnboardScreen(),
      binding: OnboardBinding(),
    ),
    GetPage(
      name: Routes.signInScreen,
      page: () => const SignInScreen(),
      binding: SignInBinding(),
    ),
    GetPage(
      name: Routes.forgetOtpVerificationScreen,
      page: () => ForgetOtpVerificationScreen(),
      binding: OtpVerificationBinding(),
    ),
    GetPage(
      name: Routes.twoFaSecurityVerify,
      page: () => const TwoFaSecurityOtpVerificationScreen(),
    ),
    GetPage(
      name: Routes.resetPasswordScreen,
      page: () => const ResetPasswordScreen(),
      binding: ResetPasswordBinding(),
    ),
    GetPage(
      name: Routes.signUpScreen,
      page: () => const SignUpScreen(),
      binding: RegistrationBinding(),
    ),
    GetPage(
        name: Routes.emailVerificationScreen,
        page: () => const EmailVerificationScreen(),
        binding: EmailVerificationBinding()),
    GetPage(
        name: Routes.notificationScreen,
        page: () => const NotificationScreen(),
        binding: NotificationBinding()),
    GetPage(
      name: Routes.referralScreen,
      page: () => const ReferralUsers(),
    ),
    GetPage(
      name: Routes.historyScreen,
      page: () => const HistoryScreen(),
    ),
    GetPage(
        name: Routes.profile,
        page: () => const ProfileScreen(),
        binding: ProfileBinding()),
    GetPage(
      name: Routes.kycVerification,
      page: () => const KycVerificationScreen(),
    ),
    GetPage(
      name: Routes.twoFaSecurity,
      page: () => const TwoFaScreen(),
    ),
    GetPage(
      name: Routes.changePassword,
      page: () => const ChangePasswordScreen(),
    ),
    GetPage(
      name: Routes.addMoneyScreen,
      page: () => const AddMoneyScreen(),
    ),
    GetPage(
      name: Routes.addMoneyCongratulation,
      page: () => const AddMoneyCongratulationScreen(),
    ),
    GetPage(
      name: Routes.moneyOutCongratulation,
      page: () => const MoneyOutCongratulationScreen(),
    ),
    GetPage(
      name: Routes.moneyOutScreen,
      page: () => MoneyOutMobileScreen(),
    ),
    GetPage(
      name: Routes.sendMoneyScreen,
      page: () => const SendMoneyScreen(),
    ),
    GetPage(
      name: Routes.sendMoneyCongratulationScreen,
      page: () => const SendMoneyCongratulationScreen(),
    ),
    // GetPage(
    //   name: Routes.investment,
    //   page: () => const InvestmentScreen(),
    // ),
    GetPage(
        name: Routes.goldStore,
        page: () => const GoldStoreScreen(),
        binding: GoldStoreBinding()),
    GetPage(
      name: Routes.goldInvestCongratulation,
      page: () => const GoldInvestCongratulationScreen(),
    ),
    GetPage(
      name: Routes.deliveryAddress,
      page: () => DeliveryAddressScreen(),
    ),
    GetPage(
      name: Routes.checkout,
      page: () => const CheckoutScreen(),
    ),
    GetPage(
      name: Routes.splashScreen,
      page: () => const SplashScreen(),
      binding: SplashBinding(),
    ),
    GetPage(
      name: Routes.moneyOutManualPaymentScreen,
      page: () => MoneyOutManualPaymentScreen(),
    ),
    GetPage(
      name: Routes.addMoneyManualPaymentScreen,
      page: () => AddMoneyManualPaymentScreen(),
    ),
    GetPage(
      name: Routes.settings,
      page: () => const SettingsScreen(),
    ),
    GetPage(
      name: Routes.transactionLogs,
      page: () => const TransactionScreen(),
    ),
    GetPage(
      name: Routes.orderLogs,
      page: () => const OrderLogsScreen(),
    ),
  ];
}
