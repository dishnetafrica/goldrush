import 'package:flutter/widgets.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/model/invest/invest_model.dart';

import '../../backend/model/common/common_success_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';
import '../../routes/routes.dart';

class InvestPlanController extends GetxController {
  List<Plan> plansList = [];
  RxString selectPlan = "".obs;
  RxString duration = "".obs;
  RxString maximumInvestAmount = "".obs;
  RxString minimumInvestAmount = "".obs;
  RxString selectSlug = "".obs;

  RxDouble fixedProfit = 0.0.obs;
  RxDouble percentProfit = 0.0.obs;
  RxDouble profitAmount = 0.0.obs;
  final investAmount = TextEditingController();

  var selectedIndex = 0.obs;
  RxBool isSelected = false.obs;

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late InvestPlanModel _investPlanModel;
  InvestPlanModel get investPlanModel => _investPlanModel;
  @override
  void onInit() {
    investPlanProcess();

    super.onInit();
  }

  @override
  void dispose() {
    investAmount.dispose();
    super.dispose();
  }

  void setSelectedIndex(int index) {
    selectedIndex.value = index;
    var selectedGold = getSelectedGold();
    if (selectedGold != null) {
      selectPlan.value = selectedGold.name;
      duration.value = selectedGold.planDuration.toString();
      maximumInvestAmount.value = selectedGold.maximumInvestment.toString();
      fixedProfit.value = selectedGold.profit;
      percentProfit.value = selectedGold.profitPercentage;
      minimumInvestAmount.value = selectedGold.minimumInvestment.toString();

      selectSlug.value = selectedGold.slug;
    }
  }

  Plan? getSelectedGold() {
    if (selectedIndex.value >= 0 && selectedIndex.value < (plansList.length)) {
      return plansList[selectedIndex.value];
    }
    return null;
  }

  double getProfitAmount() {
    profitAmount.value = double.parse(investAmount.text) + fixedProfit.value;
    return profitAmount.value;
  }

  Future<InvestPlanModel?> investPlanProcess() async {
    return RequestProcess().request<InvestPlanModel>(
      showResult: true,
      fromJson: InvestPlanModel.fromJson,
      apiEndpoint: ApiEndpoint.goldInvestPlan,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _investPlanModel = value!;

        for (var plans in _investPlanModel.data.plans) {
          plansList.add(Plan(
              id: plans.id,
              name: plans.name,
              title: plans.title,
              slug: plans.slug,
              planDuration: plans.planDuration,
              profitReturnType: plans.profitReturnType,
              minimumInvestment: plans.minimumInvestment,
              minimumInvestmentOffer: plans.minimumInvestmentOffer,
              maximumInvestment: plans.maximumInvestment,
              profit: plans.profit,
              profitPercentage: plans.profitPercentage,
              image: plans.image));
        }
        var plan = _investPlanModel.data.plans.first;
        selectPlan.value = plan.name;
        duration.value = plan.planDuration.toString();
        maximumInvestAmount.value = plan.maximumInvestment.toString();
        fixedProfit.value = plan.profit;
        percentProfit.value = plan.profitPercentage;
        minimumInvestAmount.value = plan.minimumInvestment.toString();
        selectSlug.value = plan.slug;
        investAmount.text = plan.minimumInvestment.toString();
      },
    );
  }

  static late CommonSuccessModel _commonSuccessModel;
  CommonSuccessModel get commonSuccessModel => _commonSuccessModel;

  final _isLoadingPlanPurchase = false.obs;
  bool get isLoadingPlan => _isLoadingPlanPurchase.value;

  Future<CommonSuccessModel?> onPlanPurchase() async {
    Map<String, dynamic> inputBody = {
      'invest_amount': investAmount.text,
      'slug': selectSlug.value,
    };
    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.goldInvestPlanPurchase,
      isLoading: _isLoadingPlanPurchase,
      method: HttpMethod.POST,
      body: inputBody,
      isBasic: false,
      showErrorMessage: true,
      // queryParams: {'slug': selectSlug.value},
      onSuccess: (value) {
        _commonSuccessModel = value!;

        Get.toNamed(Routes.goldInvestCongratulation);
      },
    );
  }
}
