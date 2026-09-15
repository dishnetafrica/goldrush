import 'package:flutter/material.dart';
import 'package:get_storage/get_storage.dart';

import '../model/local_storage_model/local_storage_model.dart';

class LocalStorage {
  static final GetStorage _storage = GetStorage();

  static const String tokenKey = 'token';
  static const String temporaryTokenKey = 'temporaryToken';
  static const String onboardSaveKey = 'onboardSave';
  static const String waitTimeKey = 'waitTime';
  static const String isLoggedInKey = 'isLoggedIn';
  static const String isEmailVerifiedKey = 'isEmailVerified';
  static const String isKycVerifiedKey = 'isKycVerified';
  static const String isSmsVerifiedKey = 'isSmsVerified';
  static const String baseCurrencyCodeKey = 'baseCurrencyCode';
  static const String number = 'number';

  static Future<void> save({
    String? token,
    String? temporaryToken,
    String? waitTime,
    String? baseCurrencyCode,
    bool? onboardSave,
    bool? isLoggedIn,
    bool? isEmailVerified,
    bool? isKycVerified,
    bool? isSmsVerified,
  }) async {
    if (token != null) await _storage.write(tokenKey, token);
    if (temporaryToken != null) {
      await _storage.write(temporaryTokenKey, temporaryToken);
    }
    if (onboardSave != null) await _storage.write(onboardSaveKey, onboardSave);
    if (isLoggedIn != null) await _storage.write(isLoggedInKey, isLoggedIn);
    if (isEmailVerified != null) {
      await _storage.write(isEmailVerifiedKey, isEmailVerified);
    }
    if (isKycVerified != null) {
      await _storage.write(isKycVerifiedKey, isKycVerified);
    }
    if (isSmsVerified != null) {
      await _storage.write(isSmsVerifiedKey, isSmsVerified);
    }
    if (waitTime != null) await _storage.write(waitTimeKey, waitTime);
    if (baseCurrencyCode != null) {
      await _storage.write(baseCurrencyCodeKey, baseCurrencyCode);
    }
  }

  static Future<void> saveNumber({required String number}) async {
    final box = GetStorage();

    await box.write(number, number);
    debugPrint(number);
  }

  static LocalStorageModel get model {
    return LocalStorageModel(
      _storage.read(tokenKey) ?? '',
      _storage.read(onboardSaveKey) ?? false,
      _storage.read(waitTimeKey) ?? '01:00',
      _storage.read(isLoggedInKey) ?? false,
      _storage.read(isEmailVerifiedKey) ?? false,
      _storage.read(isKycVerifiedKey) ?? false,
      _storage.read(isSmsVerifiedKey) ?? false,
      temporaryToken: _storage.read(temporaryTokenKey) ?? '',
      _storage.read(baseCurrencyCodeKey) ?? '',
    );
  }

  static String get token => _storage.read(tokenKey) ?? '';
  static String get temporaryToken => _storage.read(temporaryTokenKey) ?? '';
  static String get waitTime => _storage.read(waitTimeKey) ?? '01:00';
  static bool get onboardSave => _storage.read(onboardSaveKey) ?? false;
  static bool get isLoggedIn => _storage.read(isLoggedInKey) ?? false;
  static bool get isEmailVerified => _storage.read(isEmailVerifiedKey) ?? false;
  static bool get isKycVerified => _storage.read(isKycVerifiedKey) ?? false;
  static bool get isSmsVerified => _storage.read(isSmsVerifiedKey) ?? false;
  static String get baseCurrencyCode =>
      _storage.read(baseCurrencyCodeKey) ?? '';

  static Future<void> clear() async {
    await _storage.erase();
  }
}
