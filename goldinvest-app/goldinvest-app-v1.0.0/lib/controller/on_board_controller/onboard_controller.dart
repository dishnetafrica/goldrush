import 'package:get/get.dart';
import 'package:goldinvest/extensions/extensions.dart';

import '../../routes/routes.dart';

class OnboardController extends GetxController {
  RxInt selectedIndex = 0.obs;

  dynamic get onLogIn => Routes.signInScreen.toNamed;
  dynamic get onRegistration => Routes.signUpScreen.toNamed;
}