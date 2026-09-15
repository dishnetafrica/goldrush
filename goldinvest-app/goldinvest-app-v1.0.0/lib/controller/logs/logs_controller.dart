import 'package:get/get.dart';

import '../../backend/model/logs/order_model.dart';
import '../../backend/model/logs/profit_model.dart';
import '../../backend/model/logs/transaction_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';

class LogsController extends GetxController {
  late TransactionModel _transactionModel;
  TransactionModel get transactionController => _transactionModel;
  RxString transactionsStatus = "".obs;

  List<Transaction> transactions = [];

  List<Order> orders = [];
  List<Profit> profits = [];

  @override
  void onInit() {
    super.onInit();

    transactionInfoProcess();
    orderInfoProcess();
    profitInfoProcess();
  }

  Future<void> reloadDataTransaction() async {
    await Future.delayed(const Duration(seconds: 2));
    transactions =
        List.generate(5, (index) => transactions.single); // Updated data
  }

  final _isLoadingTransition = false.obs;
  bool get isLoadingTransaction => _isLoadingTransition.value;

  Future<TransactionModel?> transactionInfoProcess() async {
    return RequestProcess().request<TransactionModel>(
      showResult: true,
      fromJson: TransactionModel.fromJson,
      apiEndpoint: ApiEndpoint.transactions,
      isLoading: _isLoadingTransition,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        if (value != null) {
          _transactionModel = value;
          transactions = _transactionModel.data.transactions;
          transactionsStatus.value = _transactionModel.data.instructions.status;
        }
      },
    );
  }

  late OrderModel _orderModel;
  OrderModel get orderController => _orderModel;
  final _isLoadingOrder = false.obs;
  bool get isLoadingOrder => _isLoadingOrder.value;

  Future<OrderModel?> orderInfoProcess() async {
    return RequestProcess().request<OrderModel>(
      showResult: true,
      fromJson: OrderModel.fromJson,
      apiEndpoint: ApiEndpoint.orders,
      isLoading: _isLoadingOrder,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        if (value != null) {
          _orderModel = value;
          orders = _orderModel.data.order;
        }
      },
    );
  }

  late ProfitModel _profitModel;
  ProfitModel get profitModel => _profitModel;
  final _isLoadingProfit = false.obs;
  bool get isLoadingProfit => _isLoadingProfit.value;

  Future<ProfitModel?> profitInfoProcess() async {
    return RequestProcess().request<ProfitModel>(
      showResult: true,
      fromJson: ProfitModel.fromJson,
      apiEndpoint: ApiEndpoint.profit,
      isLoading: _isLoadingProfit,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        if (value != null) {
          _profitModel = value;
          profits = _profitModel.data.profits;
        }
      },
    );
  }
}
