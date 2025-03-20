import ProductsList from "@/components/Product/ProductsList";

const getProducts = async () => {
  const response = await fetch(process.env.NEXT_PUBLIC_API_URL + "/products");

  const products = await response.json();

  return products.data;
};

export default async function Page() {
  const products = await getProducts().catch((err) => {
    console.error(err);
    return [];
  });

  return (
    <main>
      <ProductsList products={products} />
    </main>
  );
}
