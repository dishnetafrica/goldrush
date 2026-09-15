class LocalStorageModel {
  final String token;
  final String temporaryToken;
  final String baseCurrencyCode;
  final bool onboardSave;
  final String waitTime;
  final bool isLoggedIn;
  final bool isEmailVerified;
  final bool isKycVerified;
  final bool isSmsVerified;

  LocalStorageModel(
    this.token,
    this.onboardSave,
    this.waitTime,
    this.isLoggedIn,
    this.isEmailVerified,
    this.isKycVerified,
    this.isSmsVerified,
    this.baseCurrencyCode, {
    required this.temporaryToken,
  });
}
