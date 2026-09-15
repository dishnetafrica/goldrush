import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/model/profile/profile_info_model.dart';
import 'package:goldinvest/backend/model/profile/states_info_model.dart'
    as statesModel;
import 'package:goldinvest/backend/utils/custom_snackbar.dart';
import 'package:goldinvest/controller/delivery_address/delivery_address_controller.dart';
import 'package:goldinvest/controller/profile/profile_controller.dart';
import 'package:goldinvest/views/investment/investment_screen.dart';
import 'package:goldinvest/widgets/common/app_bar/back_button.dart';
import 'package:goldinvest/widgets/drop_down/custom_dropdown_menu.dart';

import '../../backend/model/profile/cities_info_model.dart' as citiesModel;
import '../../backend/utils/custom_loading_api.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/common/inputs/primary_input_widget.dart';

part 'delivery_address_mobile_screen.dart';

class DeliveryAddressMobileScreen extends StatelessWidget {
  const DeliveryAddressMobileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Placeholder();
  }
}
