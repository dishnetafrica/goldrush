import 'package:flutter/material.dart';
import 'package:goldinvest/controller/send_money-controller/send_money_controller.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:get/get.dart';
import 'package:goldinvest/custom_assets/assets.gen.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../controller/increment_decrement/amount_controller_send_money.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../widgets/amount_widget/amount_widget.dart';
import '../../widgets/common/app_bar/back_button.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/inputs/primary_input_widget.dart';
import '../../widgets/common/text_labels/title_heading1_widget.dart';
import '../../widgets/common/text_labels/title_heading3_widget.dart';
import '../../widgets/send_money_widgets/send_money_bottom_sheet.dart';
import '../../widgets/send_money_widgets/calculated_widget.dart';
part 'send_money_mobile_screen.dart';

class SendMoneyScreen extends StatelessWidget {
  const SendMoneyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: SendMoneyMobileScreen());
  }
}
