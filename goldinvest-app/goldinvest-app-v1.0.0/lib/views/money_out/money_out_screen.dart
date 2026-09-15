// ignore_for_file: library_prefixes

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../backend/local_storage/local_storage.dart';
import '../../backend/model/money_out/money_out_wallet_and_gateways.dart'
    as balanceTypes;

import '../../backend/model/money_out/money_out_wallet_and_gateways.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../controller/increment_decrement/amount_controller_money_out.dart';
import '../../controller/money_out/money_out_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/responsive_layout.dart';
import '../../utils/size.dart';
import '../../widgets/amount_widget/amount_widget.dart';
import '../../widgets/common/app_bar/back_button.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/text_labels/title_heading1_widget.dart';
import '../../widgets/common/text_labels/title_heading3_widget.dart';
import '../../widgets/drop_down/custom_dropdown_menu.dart';
import '../../widgets/money_out_widgets/money_out_widget.dart';
import '../../widgets/text_span.dart/custom_text_span.dart';
part 'money_out_mobile_screen.dart';

class MoneyOutScreen extends StatelessWidget {
  const MoneyOutScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: MoneyOutMobileScreen());
  }
}
