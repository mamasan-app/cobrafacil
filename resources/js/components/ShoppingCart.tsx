import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { useLocation } from "react-router-dom";

export const ShoppingCart = () => {
  const location = useLocation();
  const selectedPlan = location.state?.plan;

  if (!selectedPlan) {
    return (
      <div className="text-center py-8">
        <p className="text-lg text-gray-600">No hay ningún plan seleccionado</p>
      </div>
    );
  }

  return (
    <div className="grid md:grid-cols-2 gap-8">
      <Card className="p-6">
        <h2 className="text-2xl font-bold mb-4">Resumen de la orden</h2>
        <div className="space-y-4">
          <div className="flex justify-between items-center">
            <div>
              <h3 className="font-semibold">{selectedPlan.name}</h3>
              <p className="text-sm text-gray-600">{selectedPlan.description}</p>
            </div>
            <p className="text-lg font-bold">${selectedPlan.price}/mes</p>
          </div>
          <hr />
          <div className="flex justify-between items-center">
            <span className="font-semibold">Total</span>
            <span className="text-xl font-bold">${selectedPlan.price}/mes</span>
          </div>
        </div>
      </Card>

      <Card className="p-6">
        <h2 className="text-2xl font-bold mb-4">Método de pago</h2>
        <form className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-1">
              Número de tarjeta
            </label>
            <input
              type="text"
              className="w-full p-2 border rounded"
              placeholder="1234 5678 9012 3456"
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-1">
                Fecha de expiración
              </label>
              <input
                type="text"
                className="w-full p-2 border rounded"
                placeholder="MM/AA"
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">CVV</label>
              <input
                type="text"
                className="w-full p-2 border rounded"
                placeholder="123"
              />
            </div>
          </div>
          <Button className="w-full">Procesar pago</Button>
        </form>
      </Card>
    </div>
  );
};