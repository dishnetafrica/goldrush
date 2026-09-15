import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/controller/delivery_address/delivery_address_controller.dart';
import 'package:goldinvest/utils/size.dart';
import 'package:goldinvest/widgets/common/app_bar/primary_app_bar.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../controller/checkout/checkout_controller.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/responsive_layout.dart';
import '../../widgets/check_out_widgets/order_product_info_widget.dart';
import '../../widgets/check_out_widgets/payment_info_widget.dart';
import '../../widgets/check_out_widgets/recept_widget.dart';
import '../../widgets/check_out_widgets/user_address_widget.dart';
import '../../widgets/common/app_bar/back_button.dart';
import '../../widgets/common/buttons/primary_button.dart';

part 'checkout_mobile_screen.dart';

class CheckoutScreen extends StatelessWidget {
  const CheckoutScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: CheckoutMobileScreen());
  }
}
