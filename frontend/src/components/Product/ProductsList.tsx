"use client";
import { Product } from "@/types/Product";
import { useEffect, useState } from "react";
import ProductCard from "./ProductCard";

export default function ProductsList(props: { products: Product[] }) {
  const [refresh, setRefresh] = useState(30);
  const { products } = props;

  const getProducts = async () => {
    const response = await fetch("http://localhost:8000/api/products");
    const products = await response.json();

    return products.data;
  };

  useEffect(() => {
    const timer = setInterval(() => {
      setRefresh((prev) => {
        if (prev <= 1) {
          getProducts();
          return 30;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, []);

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex justify-between items-center mb-6 max-sm:flex-col">
        <h1 className="text-2xl font-bold">Our Products</h1>
        <p className="text-sm text-gray-500">
          Refetching products in {refresh} seconds
        </p>
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        {products.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>
    </div>
  );
}
