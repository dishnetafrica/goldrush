import 'package:flutter/material.dart';

import 'package:get/get.dart';
import 'package:goldinvest/backend/model/add_money/add_money_info_model.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../controller/add_money_controller/add_money_controller.dart';
import '../../controller/increment_decrement/amount_controller_add_money.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/responsive_layout.dart';
import '../../utils/size.dart';
import '../../widgets/add_money_widgets/add_money_bottom_sheet.dart';
import '../../widgets/amount_widget/amount_widget.dart';
import '../../widgets/common/app_bar/back_button.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/text_labels/title_heading1_widget.dart';
import '../../widgets/common/text_labels/title_heading3_widget.dart';
import '../../widgets/drop_down/custom_dropdown_menu.dart';
import '../../widgets/text_span.dart/custom_text_span.dart';

part 'add_money_mobile_screen_layout.dart';

class AddMoneyScreen extends StatelessWidget {
  const AddMoneyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: AddMoneyMobileScreenLayout());
  }
}
